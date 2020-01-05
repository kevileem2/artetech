<?php

namespace App\Controller;

use App\Entity\Comments;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PagesController extends AbstractController
{

    /**
     * @Route({"/", "/home"}, name="home")
     *
     */

    public function HomeController()
    {
        $user = $this->getUser();
        $ClientEntity = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('email' => $user->getEmail())
            );
        if($ClientEntity) {
            $getPeriodes = $this
                ->getDoctrine()
                ->getRepository('App:Term')
                ->findBy(
                    array('client_id' => $ClientEntity->getId())
                );
            return $this->render('pages/home.html.twig', [
                'controller_name' => 'PagesController',
                'client' => $ClientEntity,
                'periodes' => $getPeriodes
            ]);
        } else {
            return $this->render('pages/noClient.html.twig', [
                'controller_name' => 'PagesController'
            ]);
        }
    }

    /**
     * @Route("detail/{id}", name="pages.detail")
     * @param $id
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     * @throws Exception
     */
    public function detail($id, Request $request, PaginatorInterface $paginator)
    {
        $user = $this->getUser();
        $ClientEntity = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('email' => $user->getEmail())
            );
        if ($ClientEntity) {
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
                            $price[$recordProjects[$i]->getId()] =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                (8 * 1.5 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.7));
                        } else {
                            $price[$recordProjects[$i]->getId()] =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                (8 * 2 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 2.2));
                        }
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            (8 * $recordClient->getHourCost() + $hoursOverTime * ($recordClient->getHourCost() * 1.2));
                    }
                } else {
                    if ($recordProjects[$i]->getDate()->format('N') > 5) {
                        if ($recordProjects[$i]->getDate()->format('N') == 6) {
                            $price[$recordProjects[$i]->getId()] =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                ($differenceTimeInNumber * $recordClient->getHourCost() * 1.5);
                        } else {
                            $price[$recordProjects[$i]->getId()] =
                                ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                                ($differenceTimeInNumber * $recordClient->getHourCost() * 2);
                        }
                    } else {
                        $price[$recordProjects[$i]->getId()] =
                            ($recordProjects[$i]->getTransport() * $recordClient->getTransportCost()) +
                            ($differenceTimeInNumber * $recordClient->getHourCost());
                    }
                }
                $totalCostTerm += $price[$recordProjects[$i]->getId()];
                $differenceTime = new \DateTime($recordProjects[$i]->getEndTime()->diff($recordProjects[$i]->getStartTime())->format('%H:%I'));
                $timeWorked[$recordProjects[$i]->getId()] = $differenceTime->diff($recordProjects[$i]->getPause());
            }
            if (!$recordTerm) {
                return $this->render('pages/notFound.html.twig', [
                    'controller_name' => 'PagesController@detail',
                    'action' => 'see'
                ]);
            }
            $getPeriodes = $this
                ->getDoctrine()
                ->getRepository('App:Term')
                ->findBy(
                    array('client_id' => $ClientEntity->getId())
                );

            $task = new Comments();
            $task
                ->setTermId($recordTerm->getId())
                ->setClientId($recordClient->getId())
            ;
            $form = $this->createFormBuilder($task)
                ->add('comment', TextareaType::class)
                ->add('save', SubmitType::class, [
                    'label' => 'Geef opmerking op deze periode!'
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
                return $this->redirectToRoute('pages.detail', array('id' =>$recordTerm->getId()));
            }

            $recordComments = $this
                ->getDoctrine()
                ->getRepository('App:Comments')
                ->findBy(
                    array('term_id' => $recordTerm->getId())
                );
            return $this->render('pages/detail.html.twig', [
                'controller_name' => 'PagesController@detail',
                'title' => 'Periode: ' . $recordClient->getName(),
                'recordClient' => $recordClient,
                'recordTerm' => $recordTerm,
                'recordProjects' => $recordProjects,
                'employeeNames' => $employeeNames,
                'prices' => $price,
                'timeWorked' => $timeWorked,
                'totalCostTerm' => $totalCostTerm,
                'periodes' => $getPeriodes,
                'form' => $form->createView(),
                'recordComments' => $recordComments,
            ]);
        } else {
            return $this->render('pages/noClient.html.twig', [
                'controller_name' => 'PagesController'
            ]);
        }
    }

    /**
     * @Route("detail/{id}/accept", name="pages.accept")
     * @param $id
     * @return Response
     */
    public function accept($id) {
        $user = $this->getUser();
        $ClientEntity = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('email' => $user->getEmail())
            );
        if ($ClientEntity) {
            $recordTerm = $this
                ->getDoctrine()
                ->getRepository('App:Term')
                ->findOneBy(
                    array('id' => $id)
                );
            if($recordTerm) {
                $recordTerm->setAccepted(true);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($recordTerm);
                $entityManager->flush();
                return $this->redirectToRoute('pages.detail', array('id' => $recordTerm->getId()));
            } else {
                return $this->render('pages/notFound.html.twig', [
                    'controller_name' => 'PagesController@detail',
                    'action' => 'see'
                ]);
            }
        }
        else {
            return $this->render('pages/noClient.html.twig', [
                'controller_name' => 'PagesController'
            ]);
        }
    }

    /**
     * @Route("detail/{id}/unAccept", name="pages.unAccept")
     * @param $id
     * @return Response
     */
    public function unAccept($id) {
        $user = $this->getUser();
        $ClientEntity = $this
            ->getDoctrine()
            ->getRepository('App:Client')
            ->findOneBy(
                array('email' => $user->getEmail())
            );
        if ($ClientEntity) {
            $recordTerm = $this
                ->getDoctrine()
                ->getRepository('App:Term')
                ->findOneBy(
                    array('client_id' => $ClientEntity->getId())
                );
            if($recordTerm) {
                $recordTerm->setAccepted(false);
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($recordTerm);
                $entityManager->flush();
                return $this->redirectToRoute('pages.detail', array('id' => $recordTerm->getId()));
            } else {
                return $this->render('pages/notFound.html.twig', [
                    'controller_name' => 'PagesController@unAccept',
                    'action' => 'see'
                ]);
            }
        }
        else {
            return $this->render('pages/noClient.html.twig', [
                'controller_name' => 'PagesController'
            ]);
        }
    }
}
