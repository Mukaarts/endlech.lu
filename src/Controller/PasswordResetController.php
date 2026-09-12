<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordResetRequestType;
use App\Form\PasswordResetType;
use App\RateLimit\ActionLimiter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Passwort zurücksetzen — der fehlende Rückweg (Feature 01, BF-04).
 *
 * ⚠ Bis heute war ein vergessenes Passwort eine Sackgasse. Seit der
 * BF-19-Reparatur wird eine E-Mail-Änderung nur noch nach Bestätigung wirksam —
 * was richtig ist, aber jeden Ausweg über die Adresse verschließt. Wer sein
 * Passwort vergaß, verlor sein Konto.
 *
 * ⚠ **Anti-Enumeration wie bei der Registrierung:** Die Antwort ist immer
 * dieselbe, egal ob die Adresse existiert. Andernfalls wäre dieses Formular ein
 * Werkzeug, um herauszufinden, wer hier ein Konto hat — und das ist bei einer
 * Barrierefreiheitsplattform eine Angabe, die niemanden etwas angeht.
 */
final class PasswordResetController extends AbstractController
{
    /** Mindestlaufzeit beider Zweige der Anfrage, siehe gleicheLaufzeitAn() (BF-137). */
    private const MINDESTDAUER_SEKUNDEN = 0.12;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
        // ⚠ Jeder Aufruf verschickt eine Mail an eine FREI WÄHLBARE Adresse —
        // dieselbe Lage wie bei der Registrierung, deshalb dieselben Werte.
        #[Autowire(service: 'limiter.password_reset')]
        private readonly RateLimiterFactoryInterface $resetLimiter,
        // ⚠ BF-138: Der einzige Weg, auf dem der Betreiber von einem Konto erfährt,
        // dessen Adresse kein Mailserver annimmt. In `prod` geht eine Warnung an
        // Sentry (siehe monolog.yaml) — ohne sie wartet der Nutzer stumm auf eine
        // Mail, die niemand schicken kann.
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/passwort-vergessen', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        // Ein angemeldeter Nutzer braucht das Formular nicht — er ändert sein
        // Passwort im Profil und kennt das alte.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $form = $this->createForm(PasswordResetRequestType::class);
        $form->handleRequest($request);

        $limiter = ActionLimiter::for($this->resetLimiter, $request->getClientIp());

        if ($form->isSubmitted() && !$limiter->isAllowed()) {
            $this->addFlash('error', $this->translator->trans('flash.password_reset_rate_limited'));

            return $this->render('security/password_reset_request.html.twig', [
                'form' => $form,
            ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter->consume();

            $begonnen = microtime(true);

            $email = strtolower(trim((string) $form->get('email')->getData()));
            $user = $this->users->findOneBy(['email' => $email]);

            if ($user instanceof User) {
                try {
                    // ⚠ BF-138: Die Adresse wird geprüft, BEVOR ein Token entsteht —
                    // dasselbe Muster wie im `RegistrationController` (BF-119).
                    // `new Address()` prüft gegen RFC 2822, und im Altbestand gibt es
                    // Adressen, die das nicht erfüllen: Der HTML5-Default des
                    // `Email`-Constraints liess sie bis BF-119 durch. Zustellbar ist
                    // an sie nichts, ein Token wäre also ein Schreibzugriff ohne jeden
                    // Zweck.
                    $empfaenger = new Address($email);

                    $token = $user->generatePasswordResetToken();
                    $this->em->flush();
                    $this->sendeLink($mailer, $user, $empfaenger, $token, $request->getLocale());
                } catch (RfcComplianceException) {
                    // ⚠ **Der Abbruch muss hier landen und nicht in einer 500er-Seite.**
                    // Ein Serverfehler nur für vorhandene Konten verrät deren Existenz
                    // deutlicher als die 12 ms, gegen die BF-137 antrat — und weil der
                    // Wurf VOR `gleicheLaufzeitAn()` lag, nahm er den Angleich mit:
                    // gemessen 19,5 ms gegen 141–146 ms im regulären Zweig.
                    //
                    // ⚠ Der Zugangsverlust bleibt und ist von hier aus nicht heilbar.
                    // Geloggt wird die **Kennung**, nicht die Adresse: `prod` schickt
                    // Warnungen an Sentry, und dort gilt `send_default_pii: false`.
                    // Wer die Adresse braucht, sieht sie mit der Kennung in der
                    // Datenbank nach.
                    $this->logger->warning(
                        'Passwort-Reset unmöglich: die gespeicherte Adresse verletzt RFC 2822 (BF-138).',
                        ['user_id' => $user->getId()],
                    );
                }
            }

            // ⚠ Dieselbe Antwort in beiden Zweigen. Der Unterschied darf sich weder
            // im Text noch im Statuscode zeigen — und seit BF-137 auch nicht in der
            // LAUFZEIT.
            $this->gleicheLaufzeitAn($begonnen);

            $this->addFlash('success', $this->translator->trans('flash.password_reset_sent'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset_request.html.twig', ['form' => $form]);
    }

    #[Route('/passwort-zuruecksetzen/{token}', name: 'app_password_reset', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, UserPasswordHasherInterface $hasher): Response
    {
        $user = $this->users->findOneBy(['passwordResetToken' => $token]);

        if (!$user instanceof User) {
            return $this->render('security/password_reset.html.twig', [
                'state' => 'invalid',
                'form' => null,
            ], new Response(null, Response::HTTP_NOT_FOUND));
        }

        if ($user->isPasswordResetTokenExpired()) {
            return $this->render('security/password_reset.html.twig', [
                'state' => 'expired',
                'form' => null,
            ], new Response(null, Response::HTTP_GONE));
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));
            $user->clearPasswordResetToken();

            // ⚠ Einen offenen Adresswechsel abräumen. Wer ein Konto übernehmen will,
            // stößt zuerst die Adressänderung an und wartet; wird danach das Passwort
            // zurückgesetzt, liefe der Vorgang trotzdem weiter. Der rechtmäßige
            // Inhaber hat gerade bewiesen, dass ihm das Postfach gehört — alles
            // Angefangene davor ist damit hinfällig.
            $user->clearPendingEmail();

            $this->em->flush();

            $this->addFlash('success', $this->translator->trans('flash.password_reset_done'));

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/password_reset.html.twig', [
            'state' => 'form',
            'form' => $form,
        ]);
    }

    /**
     * ⚠ Die Adresse kommt als geprüftes `Address`-Objekt herein, nicht als
     * Zeichenkette (BF-138). Ein `->to((string) $user->getEmail())` an dieser
     * Stelle prüfte sie erst, nachdem der Token geschrieben war, und der Wurf
     * landete ausserhalb jeder Behandlung.
     */
    private function sendeLink(MailerInterface $mailer, User $user, Address $empfaenger, string $token, string $locale): void
    {
        $url = $this->generateUrl(
            'app_password_reset',
            ['token' => $token, '_locale' => $locale],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $mail = (new TemplatedEmail())
            ->to($empfaenger)
            ->subject($this->translator->trans('email.password_reset_subject', [], null, $locale))
            ->locale($locale)
            ->htmlTemplate('email/password_reset.html.twig')
            ->context(['user' => $user, 'resetUrl' => $url]);

        try {
            $mailer->send($mail);
        } catch (TransportExceptionInterface) {
            // Der Token steht; ein Zustellproblem darf die Antwort nicht verraten.
        }
    }

    /**
     * Hält beide Zweige der Anfrage auf derselben Mindestlaufzeit (BF-137).
     *
     * ⚠ **Die gleiche Antwort genügt nicht.** Der Text und der Statuscode waren
     * schon immer identisch, die Dauer nicht: Bei bekannter Adresse entstehen Token,
     * `flush()` und ein Mail-Dispatch, bei unbekannter passiert nichts. Gemessen
     * wurden **31–36 ms gegen 23–24 ms** — zwei Wertebereiche, die sich **nicht
     * überlappen**. Eine einzige Messung genügte damit für die Frage „hat diese
     * Person hier ein Konto?".
     *
     * ⚠ **Warum eine Mindestdauer und nicht dieselbe Arbeit im leeren Zweig.** Der
     * naheliegende Weg wäre, auch ohne Konto einen Token zu erzeugen und zu
     * verwerfen — so löst die Registrierung dasselbe Problem (BF-09, Hash in beiden
     * Zweigen). Hier trüge er nicht: Die Kosten stecken in `flush()` und im
     * Mail-Dispatch, und beide lassen sich nicht folgenlos nachbauen. Eine
     * Untergrenze deckt dagegen auch den Fall ab, dass die Datenbank unter Last
     * langsamer antwortet.
     *
     * ⚠ **Die 120 ms sind mit Abstand gewählt**, nicht knapp über dem Messwert: Ein
     * Grenzwert, den der langsamere Zweig gelegentlich reißt, stellt das Leck unter
     * Last wieder her. Der Preis ist eine Anfrage, die mindestens 120 ms dauert —
     * bei fünf Anfragen je Stunde und IP fällt das niemandem auf.
     */
    private function gleicheLaufzeitAn(float $begonnen): void
    {
        $verbleibend = self::MINDESTDAUER_SEKUNDEN - (microtime(true) - $begonnen);

        if ($verbleibend > 0) {
            usleep((int) ($verbleibend * 1_000_000));
        }
    }
}
