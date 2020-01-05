<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\Term;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PrestationsController extends AbstractController
{
    /**
     * @Route("/admin/prestations", name="prestations.home")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, PaginatorInterface $paginator)
    {
        // Retrieve the entity manager of Doctrine
        $em = $this->getDoctrine()->getManager();

        // Get some repository of data, in our case we have an Employee entity
        $presentationRepository = $em->getRepository(Project::class);

        // Find all the data on the Kampen table, filter your query as you need
        $allPresentationQuery = $presentationRepository->createQueryBuilder('project')
            ->select('project')
            ->orderBy('project.date', 'ASC')
            ->getQuery();
        // Paginate the results of the query
        $records = $paginator->paginate(
        // Doctrine Query, not results
            $allPresentationQuery,
            // Define the page parameter
            $request->query->getInt('page', 1),
            // Items per page
            10
        );
        $recordProjects = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findAll();

        $clientNames = [];
        for ($i = 0; $i < count($recordProjects); $i++) {
            $clientNames[$recordProjects[$i]->getId()] = $this
                ->getDoctrine()
                ->getRepository('App:Client')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getClientId())
                )->getName();
        }
        $employeeNames = [];
        $price = [];
        $timeWorked = [];
        $totalCostTerm = 0;
        $recordTerm = [];
        $recordClient = [];
        for ($i = 0; $i < count($recordProjects); $i++) {
            $employeeNames[$recordProjects[$i]->getId()] = $this
                ->getDoctrine()
                ->getRepository('App:Employee')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getEmployeeId())
                )->getName();

            $recordClient = $this
                ->getDoctrine()
                ->getRepository('App:Client')
                ->findOneBy(
                    array('id' => $recordProjects[$i]->getClientId())
                );
            $recordTerm = $this->getDoctrine()
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
            }
        }
        return $this->render('prestations/index.html.twig', [
            'controller_name' => 'PrestationsController@index',
            'title' => 'Prestaties',
            'records' => $records,
            'recordClient' => $recordClient,
            'recordProjects' => $recordProjects,
            'recordTerm' => $recordTerm,
            'employeeNames' => $employeeNames,
            'prices' => $price,
            'clientNames' => $clientNames
        ]);
    }

    /**
     * @Route("admin/prestations/detail/{id}", name="prestations.detail")
     * @param $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function detail($id, Request $request, PaginatorInterface $paginator) {
        $recordProjects = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findOneBy(
                array('id' => $id)
            );
        $recordTerm = $this
            ->getDoctrine()
            ->getRepository('App:Term')
            ->findOneBy(
                array('id' => $recordProjects->getTermId())
            );
        $recordClient = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('id' => $recordProjects->getClientId())
            );
        $recordEmployee = $this
            ->getDoctrine()
            ->getRepository('App:Employee')
            ->findOneBy(
                array('id' => $recordProjects->getEmployeeId())
            );
        $differenceHours = $recordProjects->getEndTime()->diff($recordProjects->getStartTime())->h;
        $differenceMinutes = $recordProjects->getEndTime()->diff($recordProjects->getStartTime())->i;
        $pauseInHours = $recordProjects->getPause()->format('H');
        $pauseInMinutes = $recordProjects->getPause()->format('i');
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
            if($recordProjects->getDate()->format('N') > 5){
                if($recordProjects->getDate()->format('N') == 6){
                    $price[$recordProjects->getId()] =
                        ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                        (8 * 1.5 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.7))
                    ;
                } else {
                    $price[$recordProjects->getId()] =
                        ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                        (8 * 2 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 2.2))
                    ;
                }
            } else {
                $price[$recordProjects->getId()] =
                    ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                    (8 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() *1.2))
                ;
            }
        } else {
            if($recordProjects->getDate()->format('N') > 5) {
                if ($recordProjects->getDate()->format('N') == 6) {
                    $price[$recordProjects->getId()] =
                        ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                        ($differenceTimeInNumber * $recordClient->getHourCost() * 1.5);
                } else {
                    $price[$recordProjects->getId()] =
                        ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                        ($differenceTimeInNumber * $recordClient->getHourCost() * 2);
                }
            } else {
                $price[$recordProjects->getId()] =
                    ($recordProjects->getTransport() * $recordClient->getTransportCost()) +
                    ($differenceTimeInNumber * $recordClient->getHourCost());
            }
        }
        $differenceTime = new \DateTime($recordProjects->getEndTime()->diff($recordProjects->getStartTime())->format('%H:%I'));
        $timeWorked = $differenceTime->diff($recordProjects->getPause());
        if(!$recordTerm){
            return $this->render('prestations/notFound.html.twig', [
                'controller_name' => 'PrestationsController@detail',
                'action' => 'see'
            ]);
        }
        return $this->render('prestations/detail.html.twig', [
            'controller_name' => 'PrestationsController@detail',
            'title' => 'Prestation: '. $recordClient->getName(),
            'recordClient' => $recordClient,
            'recordTerm' => $recordTerm,
            'recordProject' => $recordProjects,
            'recordEmployee' => $recordEmployee,
            'prices' => $price,
            'timeWorked' => $timeWorked,
        ]);
    }

    /**
     * @Route("admin/prestations/delete/{id}")
     * @param $id
     * @return RedirectResponse|Response
     */
    public function delete($id) {
        $recordProject = $this
            ->getDoctrine()
            ->getRepository('App:Project')
            ->findOneBy(
                array('id' => $id)
            );
        if ($recordProject) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($recordProject);
            $entityManager->flush();
            return $this->redirectToRoute('prestations.home');
        }
        return $this->render('prestations/notFound.html.twig', [
            'controller_name' => 'PrestationsController@delete',
            'action' => 'delete',
        ]);
    }

}
