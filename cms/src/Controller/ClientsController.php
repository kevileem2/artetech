<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Term;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class ClientsController extends AbstractController
{
    /**
     * @Route("admin/klanten", name="klanten.home")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        // Retrieve the entity manager of Doctrine
        $em = $this->getDoctrine()->getManager();

        // Get some repository of data, in our case we have an Employee entity
        $clientRepository = $em->getRepository(Client::class);

        // Find all the data on the Kampen table, filter your query as you need
        $allClientQuery = $clientRepository->createQueryBuilder('klant')
            ->select('klant')
            ->orderBy('klant.name', 'ASC')
            ->getQuery();
        // Paginate the results of the query
        $records = $paginator->paginate(
        // Doctrine Query, not results
            $allClientQuery,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );
        return $this->render('clients/index.html.twig', [
            'controller_name' => 'ClientsController',
            'records' => $records
        ]);
    }

    /**
     * @Route("admin/klanten/create", name="klanten.create")
     * @param Request $request
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @return RedirectResponse|Response
     */
    public function create(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $client = new Client();
        $form = $this->createForm(RegistrationFormType::class, $user)
            ->add('name', TextType::class)
            ->add('save',SubmitType::class, [
                'label' => 'Maak klant aan!'
            ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $client = new Client();
            $client->setPassword($form->getData()->getPassword())
            ->setEmail($form->getData()->getEmail())
            ->setName($form->getData()->getName());
            $entityManager2= $this->getDoctrine()->getManager();
            $entityManager2->persist($client);
            $entityManager2->flush();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // do anything else you need here, like send an email

            return $this->redirect('create/'.$client->getId().'/prices');
        }

        return $this->render('clients/create.html.twig', [
            'registrationForm' => $form->createView(),
            'title' => 'Maak nieuwe klant aan!'
        ]);
    }

    /**
     * @Route("admin/klanten/create/{id}")
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function update(string $id, Request $request)
    {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:User')
            ->findOneBy(
                array('id' => $id)
            );

        $record->setPassword('');
        if ($record) {
            $form = $this->createFormBuilder($record)
                ->add('name', TextType::class)
                ->add('email', TextType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Voeg prijzen toe voor je klant!'
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // $form->getData() holds the submitted values
                // but, the original `$task` variable has also been updated
                $record = $form->getData();

                $client = $this->getDoctrine()
                    ->getRepository('App:Client')
                    ->findOneBy(
                        array('id'=> $id)
                    );
                $client->setPassword($form->getData()->getPassword())
                    ->setEmail($form->getData()->getEmail())
                    ->setName($form->getData()->getName());
                $entityManager2= $this->getDoctrine()->getManager();
                $entityManager2->persist($client);
                $entityManager2->flush();
                // ... perform some action, such as saving the task to the database
                // for example, if Task is a Doctrine entity, save it!
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($record);
                $entityManager->flush();

                return $this->redirectToRoute('klanten.home');
            }

            return $this->render('clients/create.html.twig', [
                'registrationForm' => $form->createView(),
                'title' => 'Maak nieuwe klant aan!'
            ]);
        }
        else {
            return $this->render('clients/notFound.html.twig', [
                'controller_name' => 'ClientsController@createPrices',
                'action' => 'create'
            ]);
        }
    }

    /**
     * @Route("admin/klanten/detail/{id}", name="klanten.detail")
     * @param $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function detail($id, Request $request, PaginatorInterface $paginator) {
        $recordClient = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $id)
            );
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findBy(
                array('client_id' => $id)
            );

        if(!$recordClient){
            return $this->render('clients/notFound.html.twig', [
                'controller_name' => 'AdminController@detail',
                'action' => 'see'
            ]);
        }
        return $this->render('clients/detail.html.twig', [
            'controller_name' => 'ClientsController@detail',
            'title' => 'Klant: '. $recordClient->getName(),
            'recordClient' => $recordClient,
            'recordTerm' => $recordTerm
        ]);
    }

    /**
     * @Route("admin/klanten/create/{id}/prices")
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createPrices(string $id, Request $request)
    {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $id)
            );
        if ($record) {
            $form = $this->createFormBuilder($record)
                ->add('hourCost', MoneyType::class)
                ->add('transportCost', MoneyType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Voeg prijzen toe voor je klant!'
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // $form->getData() holds the submitted values
                // but, the original `$task` variable has also been updated
                $record = $form->getData();

                // ... perform some action, such as saving the task to the database
                // for example, if Task is a Doctrine entity, save it!
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($record);
                $entityManager->flush();

                return $this->redirectToRoute('klanten.home');
            }

            return $this->render('clients/create-prices.html.twig', [
                'registrationForm' => $form->createView(),
                'title' => 'Maak nieuwe klant aan!'
            ]);
        }
        else {
            return $this->render('clients/notFound.html.twig', [
                'controller_name' => 'ClientsController@createPrices',
                'action' => 'create'
            ]);
        }
    }

    /**
     * @Route("admin/klanten/create/{id}/period", name="clients.period")
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createPeriod(string $id, Request $request)
    {
        $task = new Term();

        $task
            ->setClientId($id)
            ->setAccepted(false);

        // generate a form with all the values of the db table Term
        $form = $this->createFormBuilder($task)
            ->add('startTime', DateType::class)
            ->add('endTime', DateType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Maak Periode!'
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();

            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('klanten.home');
        }
        return $this->render('clients/create-period.html.twig', [
            'form' => $form->createView(),
            'title' => 'Maak nieuwe periode aan!'
        ]);
    }

    /**
     * @Route("admin/klanten/delete/{id}")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete($id) {
        $recordClient = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $id)
            );

        $recordUser = $this
            ->getDoctrine()
            ->getRepository('App:User')
            ->findOneBy(
                array('email' => $recordClient->getEmail())
            );
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('client_id' => $id)
            );
        if($recordClient){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordClient);
            $entityManager->flush();
        }
        if ($recordUser){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordUser);
            $entityManager->flush();
        }
        if ($recordTerm) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordTerm);
            $entityManager->flush();
        }

        if($recordClient || $recordUser) {
            return $this->redirectToRoute('klanten.home');
        }
        return $this->render('employee/notFound.html.twig', [
            'controller_name' => 'ClientsController@delete',
            'action' => 'delete',
        ]);
    }

    /**
     * @Route("admin/klanten/delete/{id}/period")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function deletePeriod($id) {
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $clientId = $recordTerm->getClientId();
        if ($recordTerm) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordTerm);
            $entityManager->flush();
            return $this->redirectToRoute('klanten.detail', ["id" => $clientId]);
        }
        return $this->render('clients/notFound.html.twig', [
            'controller_name' => 'ClientsController@deletePeriod',
            'action' => 'delete',
        ]);
    }
}
