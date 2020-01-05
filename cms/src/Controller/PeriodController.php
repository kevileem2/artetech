<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Term;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PeriodController extends AbstractController
{
    /**
     * @Route("admin/period", name="period.home")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        // Retrieve the entity manager of Doctrine
        $em = $this->getDoctrine()->getManager();

        $periodRepository = $em->getRepository(Term::class);

        $allPeriodQuery = $periodRepository->createQueryBuilder('term')
            ->select('term')
            ->orderBy('term.startTime', 'ASC')
            ->getQuery();
        // Paginate the results of the query
        $records = $paginator->paginate(
        // Doctrine Query, not results
            $allPeriodQuery,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findAll();
        $recordClient = [];
        for ($i = 0; $i < count($recordTerm); $i++) {
            $recordClient[$recordTerm[$i]->getId()] = $this
                ->getDoctrine()
                ->getRepository('App:Client')
                ->findOneBy(
                    array('id' => $recordTerm[$i]->getClientId())
                )
            ;
        }
        return $this->render('period/index.html.twig', [
            'controller_name' => 'PeriodController',
            'records' => $records,
            'clientNames' => $recordClient,
        ]);
    }

    /**
     * @Route("admin/period/detail/{id}", name="period.detail")
     * @param $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function detail($id, Request $request, PaginatorInterface $paginator) {
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $recordClient = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $recordTerm->getClientId())
            );
        $recordProjects = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findBy(
                array('term_id' => $recordTerm->getId())
            );
        $employeeNames = [];
        $price = [];
        $timeWorked = [];
        $totalCostTerm = 0;
        for ($i = 0; $i < count($recordProjects); $i++) {
            $employeeNames[$recordProjects[$i]->getId()] = $this
                ->getDoctrine()
                ->getRepository('App:Employee')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getEmployeeId())
                )->getName();

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
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            (8 * 1.5 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.7))
                        ;
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            (8 * 2 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 2.2))
                        ;
                    }
                } else {
                    $price[$recordProjects[$i]->getId()] =
                        ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                        (8 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() *1.2))
                    ;
                }
            } else {
                if($recordProjects[$i]->getDate()->format('N') > 5) {
                    if($recordProjects[$i]->getDate()->format('N') == 6){
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient->getHourCost() * 1.5)
                        ;
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient->getHourCost() * 2)
                        ;
                    }
                } else {
                    $price[$recordProjects[$i]->getId()] =
                        ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                        ($differenceTimeInNumber * $recordClient->getHourCost())
                    ;
                }
            }
            $totalCostTerm += $price[$recordProjects[$i]->getId()];
            $differenceTime = new \DateTime($recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->format('%H:%I'));
            $timeWorked[$recordProjects[$i]->getId()] = $differenceTime->diff($recordProjects[$i]->getPause());
        }
        if(!$recordTerm){
            return $this->render('period/notFound.html.twig', [
                'controller_name' => 'PeriodController@detail',
                'action' => 'see'
            ]);
        }
        $recordComments = $this
            ->getDoctrine()
            ->getRepository('App:Comments')
            ->findBy(
                array('term_id' => $recordTerm->getId())
            );
        return $this->render('period/detail.html.twig', [
            'controller_name' => 'PeriodController@detail',
            'title' => 'Periode: '. $recordClient->getName(),
            'recordClient' => $recordClient,
            'recordTerm' => $recordTerm,
            'recordProjects' => $recordProjects,
            'employeeNames' => $employeeNames,
            'prices' => $price,
            'timeWorked' => $timeWorked,
            'totalCostTerm' => $totalCostTerm,
            'recordComments' => $recordComments
        ]);
    }

    /**
     * @Route("admin/period/create", name="period.create")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function create(Request $request)
    {
        $task = new Term();
        $task->setAccepted(false);
        $clients = $this->getDoctrine()->getRepository('App:Client')->findAll();
        $clientNames = [];
        for ($i = 0; $i < count($clients); $i++){
            $clientNames[$clients[$i]->getName()] = $clients[$i]->getId();
        }
        // generate a form with all the values of the db table Term
        $form = $this->createFormBuilder($task)
            ->add('startTime', DateType::class)
            ->add('endTime', DateType::class)
            ->add('save', SubmitType::class, [
                'label' => 'Maak Periode!'
            ])
            ->getForm();
        $form->add('client_id',ChoiceType::class, array(
            'choices' => $clientNames
        ));
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

            return $this->redirectToRoute('period.home');
        }
        return $this->render('period/create.html.twig', [
            'form' => $form->createView(),
            'title' => 'Maak nieuwe periode aan!'
        ]);
    }

    /**
     * @Route("admin/period/create/{id}", name="period.update")
     * @param $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function update($id, Request $request)
    {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $clients = $this->getDoctrine()->getRepository('App:Client')->findAll();
        $clientNames = [];
        for ($i = 0; $i < count($clients); $i++){
            $clientNames[$clients[$i]->getName()] = $clients[$i]->getId();
        }
        if($record) {
            $form = $this->createFormBuilder($record)
                ->add('startTime', DateType::class)
                ->add('endTime', DateType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Maak Periode!'
                ])
                ->getForm();
            $form->add('client_id',ChoiceType::class, array(
                'choices' => $clientNames
            ));
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

                return $this->redirectToRoute('period.home');
            }
            return $this->render('period/create.html.twig', [
                'controller_name' => 'PeriodController@update',
                'title' => 'Update Periode ',
                'form' => $form->createView(),
            ]);
        } else {
            return $this->render('period/notFound.html.twig', [
                'controller_name' => 'PeriodController@update',
                'action' => 'update'
            ]);
        }
    }

    /**
     * @Route("admin/period/create/{id}/project")
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function createProject(string $id, Request $request)
    {
        $task = new Project();
        $task->setTermId($id);
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $task->setClientId($recordTerm->getClientId());
        $employee = $this->getDoctrine()->getRepository('App:Employee')->findAll();
        $employeeNames = [];
        for ($i = 0; $i < count($employee); $i++){
            $employeeNames[$employee[$i]->getName()] = $employee[$i]->getId();
        }
        if ($recordTerm) {
            $form = $this->createFormBuilder($task)
                ->add('startTime', TimeType::class)
                ->add('endTime', TimeType::class)
                ->add('date', DateType::class)
                ->add('pause', TimeType::class)
                ->add('activities', TextareaType::class)
                ->add('materials', TextareaType::class)
                ->add('transport', IntegerType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Voeg project toe voor je klant!'
                ])
                ->getForm();
            $form->add('employee_id',ChoiceType::class, array(
                'choices' => $employeeNames
            ));
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

                return $this->redirectToRoute('period.home');
            }

            return $this->render('period/createProject.html.twig', [
                'form' => $form->createView(),
                'title' => 'Maak nieuwe klant aan!'
            ]);
        }
        else {
            return $this->render('period/notFound.html.twig', [
                'controller_name' => 'PeriodController@createProject',
                'action' => 'create'
            ]);
        }
    }

    /**
     * @Route("admin/period/create/{id}/project/{projectId}", name="project.create")
     * @param string $id
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function editProject(string $id, string $projectId, Request $request)
    {
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $task = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findOneBy(
                array('id' => $projectId)
            );
        $employee = $this->getDoctrine()->getRepository('App:Employee')->findAll();
        $employeeNames = [];
        for ($i = 0; $i < count($employee); $i++){
            $employeeNames[$employee[$i]->getName()] = $employee[$i]->getId();
        }
        if ($recordTerm) {
            $form = $this->createFormBuilder($task)
                ->add('startTime', TimeType::class)
                ->add('endTime', TimeType::class)
                ->add('date', DateType::class)
                ->add('pause', TimeType::class)
                ->add('activities', TextareaType::class)
                ->add('materials', TextareaType::class)
                ->add('transport', IntegerType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Voeg project toe voor je klant!'
                ])
                ->getForm();
            $form->add('employee_id',ChoiceType::class, array(
                'choices' => $employeeNames
            ));
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

                return $this->redirectToRoute('period.home');
            }

            return $this->render('period/createProject.html.twig', [
                'form' => $form->createView(),
                'title' => 'Maak nieuwe klant aan!'
            ]);
        }
        else {
            return $this->render('period/notFound.html.twig', [
                'controller_name' => 'PeriodController@createProject',
                'action' => 'create'
            ]);
        }
    }

    /**
     * @Route("admin/period/detail/{id}/excel", name="period.excel")
     * @param $id
     * @return Response
     * @throws Exception
     */
    public function excel($id) {
        $record = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );
        $recordClient = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $record->getClientId())
            );
        $recordProjects = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findBy(
                array('term_id' => $record->getId())
            );
        $spreadsheet = new Spreadsheet();
        $Excel_writer = new Xlsx($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();
        if($record && $recordClient) {
            $activeSheet
                ->setCellValue('A1', 'Klant')
                ->setCellValue('A2', $recordClient->getName())
                ->setCellValue('B1', 'Start Datum')
                ->setCellValue('B2', $record->getStartTime()->format('Y-m-d'))
                ->setCellValue('C1', 'Eind Datum')
                ->setCellValue('C2', $record->getEndTime()->format('Y-m-d'))
                ->setCellValue('D1', "Kost Werknemer")
                ->setCellValue('D2', $recordClient->getHourCost())
                ->setCellValue('E1', 'Transport kost')
                ->setCellValue('E2', $recordClient->getTransportCost())
                ->setCellValue('F1', 'Totale kost prijs van deze periode')
                ->setCellValue('A4', 'Werknemer')
                ->setCellValue('B4', 'Datum')
                ->setCellValue('C4', 'Tijd Gewerkt')
                ->setCellValue('D4', 'Start tijd')
                ->setCellValue('E4', 'Eind tijd')
                ->setCellValue('F4', 'Pauze')
                ->setCellValue('G4', 'Prijs')
                ->setCellValue('H4', 'Activiteiten')
                ->setCellValue('I4', 'Materialen')
                ->setCellValue('J4', 'Transport in km')
            ;
            $totalCostTerm = 0;
            for ($i = 0; $i < count($recordProjects); $i++) {
                $employee = $this
                    ->getDoctrine()
                    ->getRepository('App:Employee')
                    ->findOneBy(
                        array('id' => $recordProjects[$i]->getEmployeeId())
                    )->getName();
                $differenceHours = $recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->h;
                $differenceMinutes = $recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->i;
                $pauseInHours = $recordProjects[$i]->getPause()->format('H');
                $pauseInMinutes = $recordProjects[$i]->getPause()->format('i');
                $pauseInNumber = $pauseInHours + $pauseInMinutes / 60;
                $differenceTimeInNumber = $differenceHours + $differenceMinutes / 60 - $pauseInNumber;
                $hoursOverTime = 0;
                if ($differenceHours - $pauseInNumber >= 8) {
                    if ($differenceHours - $pauseInHours === 8) {
                        $hoursOverTime = $differenceMinutes / 60;
                    } else {
                        $hoursOverTime = $differenceTimeInNumber - 8;
                    }
                }
                if ($hoursOverTime !== 0) {
                    if ($recordProjects[$i]->getDate()->format('N') > 5) {
                        if ($recordProjects[$i]->getDate()->format('N') == 6) {
                            $price =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                (8 * 1.5 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.7));
                        } else {
                            $price =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                (8 * 2 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 2.2));
                        }
                    } else {
                        $price =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            (8 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.2));
                    }
                } else {
                    if($recordProjects[$i]->getDate()->format('N') > 5){
                        if ($recordProjects[$i]->getDate()->format('N') == 6) {
                            $price =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                ($differenceTimeInNumber * $recordClient->getHourCost() * 1.5);
                        } else {
                            $price =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                ($differenceTimeInNumber * $recordClient->getHourCost() * 2);
                        }
                    } else {
                        $price =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient->getHourCost());
                    }
                }
                $totalCostTerm += $price;
                $differenceTime = new \DateTime($recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->format('%H:%I'));
                $timeWorked = $differenceTime->diff($recordProjects[$i]->getPause());
                $coordinate = $i+5;
                $activeSheet
                    ->setCellValue('A'.$coordinate, $employee)
                    ->setCellValue('B'.$coordinate, $recordProjects[$i]->getDate())
                    ->setCellValue('C'.$coordinate, $timeWorked->format('%H:%I'))
                    ->setCellValue('D'.$coordinate, $recordProjects[$i]->getStartTime()->format('H:i'))
                    ->setCellValue('E'.$coordinate, $recordProjects[$i]->getEndTime()->format('H:i'))
                    ->setCellValue('F'.$coordinate, $recordProjects[$i]->getPause()->format('H:i'))
                    ->setCellValue('G'.$coordinate, $price)
                    ->setCellValue('H'.$coordinate, $recordProjects[$i]->getActivities())
                    ->setCellValue('I'.$coordinate, $recordProjects[$i]->getMaterials())
                    ->setCellValue('J'.$coordinate, $recordProjects[$i]->getTransport())
                ;
            }
            $activeSheet->setCellValue('F2', $totalCostTerm);
            $filename = "periode-klant-".$recordClient->getName().".xlsx";
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'. $filename);
            header('Cache-Control: max-age=0');
            $Excel_writer->save('php://output');
        }
        return $this->redirectToRoute('period.detail', array('id' => $id));
    }

    /**
     * @Route("admin/period/delete/{id}")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete($id) {
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $id)
            );

        if ($recordTerm) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordTerm);
            $entityManager->flush();
            return $this->redirectToRoute('period.home');
        }
        return $this->render('period/notFound.html.twig', [
            'controller_name' => 'PeriodController@deletePeriod',
            'action' => 'delete',
        ]);
    }

    /**
     * @Route("admin/period/delete/{id}/project")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function deletePeriod($id) {
        $recordProject = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findOneBy(
                array('term_id' => $id)
            );
        if ($recordProject) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordProject);
            $entityManager->flush();
            return $this->redirectToRoute('period.detail', ["id" => $id]);
        }
        return $this->render('period/notFound.html.twig', [
            'controller_name' => 'PeriodController@deleteProject',
            'action' => 'delete',
        ]);
    }
}
