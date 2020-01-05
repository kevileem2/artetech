<?php

namespace App\Controller;

use App\Entity\Employee;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmployeeController extends AbstractController
{
    /**
     * @Route("admin/werknemers", name="werknemers.home")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        // Retrieve the entity manager of Doctrine
        $em = $this->getDoctrine()->getManager();

        // Get some repository of data, in our case we have an Employee entity
        $employeeRepository = $em->getRepository(Employee::class);

        // Find all the data on the Kampen table, filter your query as you need
        $allEmployeeQuery = $employeeRepository->createQueryBuilder('employee')
            ->select('employee')
            ->orderBy('employee.name', 'ASC')
            ->getQuery();
        // Paginate the results of the query
        $records = $paginator->paginate(
        // Doctrine Query, not results
            $allEmployeeQuery,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );
        return $this->render('employee/index.html.twig', [
            'controller_name' => 'EmployeeController',
            'records' => $records
        ]);
    }

    /**
     * @Route("admin/werknemers/detail/{id}")
     * @param $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function detail($id, Request $request, PaginatorInterface $paginator) {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Employee')
            ->findOneBy(
                array('id' => $id)
            );

        $recordProjects = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findBy(
                array('employee_id' => $id)
            );
        $recordClient = [];
        $recordTerm = [];
        $price = [];
        $totalCostTerm = 0;
        $timeWorked = [];
        for ($i = 0; $i < count($recordProjects); $i++) {
            $recordClient[$recordProjects[$i]->getId()] = $this
                ->getDoctrine()
                ->getRepository('App:Client')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getClientId())
                );
            $recordTerm[$recordProjects[$i]->getId()] = $this->getDoctrine()
                ->getRepository('App:Term')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getTermId())
                );
            $differenceHours = $recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->h;
            $differenceMinutes = $recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->i;
            $pauseInHours = $recordProjects[$i]->getPause()->format('H');
            $pauseInMinutes = $recordProjects[$i]->getPause()->format('i');
            $pauseInNumber = $pauseInHours + $pauseInMinutes/60;
            $differenceTimeInNumber = $differenceHours + $differenceMinutes/60 - $pauseInNumber;
            $hoursOverTime = 0;
            if($differenceHours - $pauseInNumber >= 8){
                if($differenceHours - $pauseInHours === 8){
                    $hoursOverTime = $differenceMinutes/60;
                } else {
                    $hoursOverTime = $differenceTimeInNumber - 8;
                }
            }
            if($hoursOverTime !== 0) {
                if($recordProjects[$i]->getDate()->format('N') > 5){
                    if($recordProjects[$i]->getDate()->format('N') == 6){
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                            (8 * 1.5 * $recordClient[$recordProjects[$i]->getId()]->getHourCost() + $hoursOverTime * ($recordClient[$recordProjects[$i]->getId()]->getHourCost() * 1.7))
                        ;
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                            (8 * 2 * $recordClient[$recordProjects[$i]->getId()]->getHourCost() + $hoursOverTime * ($recordClient[$recordProjects[$i]->getId()]->getHourCost() * 2.2))
                        ;
                    }
                } else {
                    $price[$recordProjects[$i]->getId()] =
                        ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                        (8 * $recordClient[$recordProjects[$i]->getId()]->getHourCost() + $hoursOverTime * ($recordClient[$recordProjects[$i]->getId()]->getHourCost() *1.2))
                    ;
                }
            } else {
                if($recordProjects[$i]->getDate()->format('N') > 5){
                    if($recordProjects[$i]->getDate()->format('N') == 6){
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient[$recordProjects[$i]->getId()]->getHourCost() * 1.5)
                        ;
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient[$recordProjects[$i]->getId()]->getHourCost() * 2)
                        ;
                    }
                } else {
                    $price[$recordProjects[$i]->getId()] =
                        ($recordProjects[$i]->getTransport() * $recordClient[$recordProjects[$i]->getId()]->getTransportCost()) +
                        ($differenceTimeInNumber * $recordClient[$recordProjects[$i]->getId()]->getHourCost())
                    ;
                }
            }
            $totalCostTerm += $price[$recordProjects[$i]->getId()];
            $differenceTime = new \DateTime($recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->format('%H:%I'));
            $timeWorked[$recordProjects[$i]->getId()] = $differenceTime->diff($recordProjects[$i]->getPause());
        }
        if(!$record){
            return $this->render('employee/notFound.html.twig', [
                'controller_name' => 'EmployeeController@detail',
                'action' => 'see'
            ]);
        }
        return $this->render('employee/detail.html.twig', [
            'controller_name' => 'EmployeeController@detail',
            'title' => 'Werknemer: '. $record->getName(),
            'record' => $record,
            'recordTerm' => $recordTerm,
            'recordClients' => $recordClient,
            'recordProjects' => $recordProjects,
            'timeWorked' => $timeWorked,
            'prices' => $price
        ]);
    }

    /**
     * @Route("admin/werknemers/create", name="werknemers.create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function create(Request $request)
    {
        $task = new Employee();

        // generate a form with all the values of the db table Employee
        $form = $this->createFormBuilder($task)
            ->add('name', TextType::class)
            ->add('email', TextType::class)
            ->add('password', PasswordType::class)
            ->add('save',SubmitType::class, [
                'label' => 'Maak werknemer aan!'
            ])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $task = $form->getData();
            $encoded = password_hash($task->getPassword(), PASSWORD_BCRYPT);
            $task->setPassword($encoded);
            // ... perform some action, such as saving the task to the database
            // for example, if Task is a Doctrine entity, save it!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('werknemers.home');
        }
        return $this->render('employee/create.html.twig', [
            'form' => $form->createView(),
            'controller_name' => "EmployeeController@create",
            'title' => 'Maak nieuwe werknemer aan'
        ]);
    }

    /**
     * @Route("admin/werknemers/create/{id}")
     * @param $id
     * @param $request
     * @return Response
     * @throws Exception
     */
    public function update(string $id, Request $request) {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Employee')
            ->findOneBy(
                array('id' => $id)
            );

        if($record) {
            $record->setPassword('');
            $form = $this->createFormBuilder($record)
                ->add('name', TextType::class)
                ->add('email', TextType::class)
                ->add('password', PasswordType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Verander werknemer!'
                ])
                ->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // $form->getData() holds the submitted values
                // but, the original `$task` variable has also been updated
                $record = $form->getData();
                $encoded = password_hash($record->getPassword(), PASSWORD_BCRYPT);
                $record->setPassword($encoded);
                // ... perform some action, such as saving the task to the database
                // for example, if Task is a Doctrine entity, save it!
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($record);
                $entityManager->flush();

                return $this->redirectToRoute('werknemers.home');
            }
            return $this->render('employee/create.html.twig', [
                'controller_name' => 'AdminController@update',
                'title' => 'Update Werknemer: '. $record->getName(),
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('employee/notFound.html.twig', [
                'controller_name' => 'EmployeeController@update',
                'action' => 'update'
            ]);
        }
    }

    /**
     * @Route("admin/werknemers/delete/{id}")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete($id) {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Employee')
            ->findOneBy(
                array('id' => $id)
            );
        if($record){
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($record);
            $entityManager->flush();
            return $this->redirectToRoute('werknemers.home');
        }
        return $this->render('employee/notFound.html.twig', [
            'controller_name' => 'EmployeeController@delete',
            'action' => 'delete',
        ]);
    }

}
