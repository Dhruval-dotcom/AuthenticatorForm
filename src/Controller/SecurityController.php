<?php

namespace App\Controller;

use App\Entity\Question;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Psr\Log\LoggerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/home', name: 'app_home')]
    public function home(LoggerInterface $loggerInterface): Response
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'User, Welcome to home page',
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        throw new \Exception('Logout() should never be reached');
    }

    #[Route('/admin', name: 'admin')]
    #[IsGranted('ROLE_USER')]
    public function admin(): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // if(!$this->isGranted('ROLE_ADMIN')){
        //     throw $this->createAccessDeniedException('No Access for you');
        // }
        return $this->render('security/index.html.twig', [
            'controller_name' => 'Admin',
        ]);
    }

    #[Route('/admin/login', name: 'admin2')]
    public function admin2(): Response
    {
        return new Response('Pretend that admin is logging');
    }

    #[Route('/admin/answers', name: 'admin_answers')]
    public function adminAnswers(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMMENT_ADMIN');
        return new Response('Admin answers');
    }

    #[Route('/question', name: 'question')]
    public function question(EntityManagerInterface $em): Response
    {
        return $this->render('question/questions.html.twig', [
            'question' => $em->getRepository(Question::class)->findAll(),
        ]);
    }

    #[Route('/question/edit/{id}', name: 'question_edit')]
    public function questionEdit(Question $question): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $question);
        // if($question->getOwner() !== $this->getUser()){
        //     throw $this->createAccessDeniedException(('You are NOt owner'));
        // }
        return $this->render('question/edit.html.twig', [
            'val' => $question,
        ]);
    }

    #[Route("/authentication/2fa/enable", name:"app_2fa_enable")]
    #[IsGranted("ROLE_USER")]
    public function enable2fa(TotpAuthenticatorInterface $totpAuthenticator, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user->isTotpAuthenticationEnabled()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());

            $entityManager->flush();
        }
        // dd($totpAuthenticator->getQRContent($user));
        return $this->render('security/enable2fa.html.twig');
    }

    #[Route("/authentication/2fa/qr-code", name:"app_qr_code")]
    public function displayGoogleAuthenticatorQrCode(TotpAuthenticatorInterface $totpAuthenticatorInterface)
    {
        // $qrCode is provided by the endroid/qr-code library. See the docs how to customize the look of the QR code:
        // https://github.com/endroid/qr-code
        $qrCode = $totpAuthenticatorInterface->getQRContent($this->getUser());
        $result = Builder::create()
        ->data($qrCode)
        ->build();

        return new Response($result->getString() , 200, ['Content-Type' => 'image/png']);
    }
}
