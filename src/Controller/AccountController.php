<?php

namespace jjansen\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use jjansen\Entity\User;
use jjansen\Service\SecurityService;
use jjansen\Service\TemplateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AccountController extends AbstractController
{
    private TemplateService $templating;
    private SecurityService $sService;
    private UserPasswordHasherInterface $passwordHasher;
    private AuthenticationUtils $authenticationUtils;

    public function __construct(
        TemplateService $templating,
        SecurityService $securityService,
        UserPasswordHasherInterface $passwordHasher,
        AuthenticationUtils $authenticationUtils
    ) {
        $this->templating = $templating;
        $this->sService = $securityService;
        $this->pHasher = $passwordHasher;
        $this->aUtils = $authenticationUtils;
    }

    /**
     * creates a user with given Request
     *
     * @param Request $request
     * @return Response
     */
    public function registerAction(Request $request): Response
    {
        $errors = [];
        // check if user requested a registration
        if ($request->isMethod(Request::METHOD_POST)) {
            // start registration
            $errors = $this->initRegister($request);
            // finally return to mainsite if no errors
            if (count($errors) <= 0) {
                // register user
                if ($this->RegisterUser($request->get('uid'), $request->get('name'), $request->get('last'),
                        $request->get('pwd'), $request->get('email')) != true) {
                    $errors['conn'] = "connection failed";
                }
                return new RedirectResponse($this->generateUrl('default', []));
            }
        }

        $content = $this->templating->render('Account/register.html.twig', [
            'errors' => $errors,
            'request' => $request
        ]);
        return new Response($content);
    }

    // ################################## PRIVATE FUNCTIONS #####################

    /**
     * Validation: validates a user with given Request
     *
     * @param Request $request
     * @return array
     */
    private function initRegister(Request $request): array
    {
        // ## whole function for registration system
        // create new error arrays
        $errors = [];
        // check if needed posts are set
        if (!$request->get('uid') || !$request->get('email') || !$request->get('name') || !$request->get('last') ||
            !$request->get('pwd') || !$request->get('pwdrepeat')) {
            $errors['request'] = "all post requests needs to be set";
        }
        //filter variables
        $uid = $this->sService->filterInput($request->get('uid'));
        $email = $this->sService->filterInputEmail($request->get('email'));
        $name = $this->sService->filterInput($request->get('name'));
        $last = $this->sService->filterInput($request->get('last'));
        $pwd = $this->sService->filterInput($request->get('pwd'));
        $pwd2 = $this->sService->filterInput($request->get('pwdrepeat'));

        // checks if inputs are valid
        if ($this->sService->checkInput($uid) != true) {
            $errors['uid'] = "Der Benutzername darf keine Sonderzeichen enthalten";
        }
        if ($this->sService->checkInputEmail($email) != true) {
            $errors['email'] = "Bitte gebe eine echte E-Mail Adresse ein";
        }
        if ($this->sService->checkInputOptional($name) != true) {
            $errors['name'] = "Der Name darf keine Sonderzeichen enthalten";
        }
        if ($this->sService->checkInputOptional($last) != true) {
            $errors['last'] = "Der Nachname darf keine Sonderzeichen enthalten";
        }
        if ($this->sService->checkInputPWMatch($pwd, $pwd2) != true) {
            $errors['pwd'] = "Die Passwörter stimmen nicht überein";
        }
        if ($this->sService->checkInputPW($pwd) != true) {
            $errors['pwd2'] = "Passwort muss min. 8 Zeichen, 1 Klein- & Großbuchstabe, 1 Sonderzeichen, 1 Zahl enthalten";
        }
        /*if($this->sService->checkUIDExist($name, $email) != null){
            $errors['email'] .= "Der Nutzername oder die E-Mail Adresse existiert bereits";
        }*/
        return $errors;
    }
    // WARNING: UNSECURE EXPERIMENTAL FUNCTION, DONT USE IT

    /**
     * creates a new user with given data
     *
     * @param string $uid
     * @param string $name
     * @param string $last
     * @param string $pwd_plain
     * @param string $email
     * @return bool
     */
    private function RegisterUser(string $uid, string $name, string $last, string $pwd_plain, string $email): bool
    {
        $entityManager = $this->getDoctrine()->getManager();

        // create new User object
        $user = new User();
        $user->setUuid($uid);
        $user->setName($name);
        $user->setLastName($last);
        // hash pwd
        $hashedPwd = $this->pHasher->hashPassword($user, $pwd_plain);
        $user->setPassword($hashedPwd);
        $user->setEmail($email);

        // insert user into database
        $entityManager->persist($user);
        $entityManager->flush();
        return true;
    }

}