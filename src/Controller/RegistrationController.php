<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager, 
        VerifyEmailHelperInterface $verifyEmailHelperInterface   
    ): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email


            // return $userAuthenticatorInterface->authenticateUser(
            //     $user,
            //     $formLoginAuthenticator,
            //     $request
            // );

            $signatureComponents = $verifyEmailHelperInterface->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            // TODO: in a real app, send this as an email!
            $this->addFlash('success', 'Confirm your email at: '.$signatureComponents->getSignedUrl());
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify', name: 'app_verify_email')]
    public function VerifyUserEmail(
        Request $request, 
        VerifyEmailHelperInterface $verifyEmailHelperInterface,
        UserRepository $userRepository,
        EntityManagerInterface $em
        ) : Response
    {
        $user = $userRepository->find($request->query->get('id'));
        if(!$user){
            throw $this->createNotFoundException();
        }

        try{
            $verifyEmailHelperInterface->validateEmailConfirmation(
                $request->getUri(),
                $user->getId(),
                $user->getEmail(),
            );
        } catch (VerifyEmailExceptionInterface $e) {
            $this->addFlash('error', $e->getReason());

            return $this->redirectToRoute('app_register');
        }
        $user->setIsVerified(true);
        $em->flush();

        $this->addFlash('success', 'Account Verified! You can now log in.');

        return $this->redirectToRoute('app_login');
    }

    #[Route("/verify/resend", name:"app_verify_resend_email")]
     
    public function resendVerifyEmail()
    {
        return $this->render('registration/resend_verify_email.html.twig');
    }
}
