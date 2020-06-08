<?php
namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Basket;
use App\Etity\User;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use App\Service\Mailer;
use Symfony\Component\HttpFoundation\Session\Session;

class CheckoutController extends AbstractController
{
    private $config;

    private $stripePk;

    private $basket;

    private $session;

    private $produits;

    public function __construct(EntityManagerInterface $objectManager)
    {
        $this->basket = new Basket($objectManager);
        $this->config = require(__DIR__ . '/../../config/stripe.php.dist');
        $this->stripePk = $this->config['publishable_key'];
        $this->session = new Session();
    }

    public function address(Request $req, AddressRepository $addressRepository, \Swift_Mailer $mailer)
    {
        if (!$this->basket->hasProducts()) {
            return $this->redirectToRoute('basket_show');
        }
        $billingAddress = $addressRepository
            ->findCurrentWithType($this->getUser()->getId(), 'billing');
        if (null === $billingAddress) {
            $this->addFlash('info', 'Veuillez renseigner une adresse de facturation avant de continuer');
            return $this->redirectToRoute('user_account');
        }

        $address = $addressRepository
            ->findCurrentWithType($this->getUser()->getId(), 'shipping');
        $form = $this->createForm(AddressType::class, $address);
        
        $form->handleRequest($req);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $address = $form->getData();
           
            $uow = $this->getDoctrine()
                ->getManager()
                ->getUnitOfWork();
            $uow->computeChangeSets();

            if ($uow->isEntityScheduled($address)) {
                $address = clone $address;
                $address->setDateCreated(new \DateTime());
            }

            $address->setType('shipping')
                    ->setCountry('France')
                    ->setUser($this->getUser());
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($address);
            $em->flush();

            $this->session->set('checkout/address', true);
            
            $produits = $this->basket->getProducts();

            $message = (new \Swift_Message('VS-GAME CODE'))
                ->setFrom('victorsedaros@gmail.com')
                ->setTo($this->getUser()->getEmail())
                ->setSubject('VS-GAMING - Your code here :')
                ->setBody(
                    $this->renderView(
                        'emails/order_confirmation.html.twig',
                        ['produits' => $produits]
                    )
                )
            ;
            $produits = $this->basket->clear();             
            $mailer->send($message);
            $this->addFlash('message','Mail à été envoyé');
            return $this->redirectToRoute('index');

        }

        return $this->render('shop/checkout/address.html.twig', [
            'address_form' => $form->createView(),
        ]);
    }

}
