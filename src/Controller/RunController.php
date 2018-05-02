<?php

namespace App\Controller;

use App\Entity\Run;
use App\Form\RunType;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class RunController extends Controller
{
    /**
     * @Route("/run/add", name="addRun")
     */
    public function addRun(Request $req, EntityManagerInterface $em)
    {
        if ($this->getUser()) {
            $run = new Run();
            //créer et traite le formulaire d'ajout de trajet
            $form = $this->createForm(RunType::class, $run);
            $form->handleRequest($req);

            if ($form->isSubmitted() && $form->isValid()) {
                //si le form est validé, set le driver avec le current user
                $run->setDriver($this->getUser());
                $em->persist($run);
                $em->flush();

                $this->addFlash('success', 'Your run has been successfully added');
                return $this->redirectToRoute('account');
            }

            return $this->render('run/addRun.html.twig', ["runForm" => $form->createView()]);
        } else{
            return $this->redirectToRoute('home');
        }

}


    /**
     * @Route("/run/delete/{id}", name="deleteRun")
     */
    public function suppRun(EntityManagerInterface $em, $id){
        $run = $em->getRepository(Run::class)->find($id);

        if($this->getUser()) {
            if ($run->getDriver() === $this->getUser()) {
                $em->remove($run);
                $em->flush();
                $this->addFlash('success', 'This run has been removed, an email has been sent to pasengers');
                return $this->redirectToRoute('account');
            }else{
                $this->addFlash('danger', 'You can\'t remove this run');
                return $this->redirectToRoute('account');
            }
        }else{
            return $this->redirectToRoute('home');
        }

    }

    /**
     * @Route("/run/edit/{id}", name="editRun")
     */
    public function editRun(EntityManagerInterface $em, Request $req, $id){
        if($this->getUser()){
            $run = $em->getRepository(Run::class)->find($id);

            if($run->getDriver() === $this->getUser()){

                //créer et traite le formulaire de modification de trajet
                $form = $this->createForm(RunType::class, $run);
                $form->handleRequest($req);

                if($form->isSubmitted() && $form->isValid()){
                    $em->flush();
                    $this->addFlash('success', 'Run updated successfully');
                    return $this->redirectToRoute('account');
                }

                return $this->render('run/addRun.html.twig', ['runForm'=> $form->createView()]);

            }else{
                $this->addFlash('danger', 'You can\'t update this run');
                return $this->redirectToRoute('account');
            }
        }else{
            return $this->redirectToRoute('home');
        }
    }


    /**
     * @Route("/run/{id}", name="detailRun")
     */
    public function detailRun(EntityManagerInterface $em, Request $req, $id){
        $run = $em->getRepository(Run::class)->find($id);
        return $this->render('run/detailRun.html.twig', ['run'=> $run]);
    }

    /**
     * @Route("/runs", name="listRuns")
     */
    public function listRuns(EntityManagerInterface $em){
        $runs = $em->getRepository(Run::class)->findAll();
        return $this->render('run/listRuns.html.twig', ['runs'=> $runs]);

    }


    /**
     * @Route("/run/reserve/{id}", name="reserveRun")
     */
    public function reserveRun(EntityManagerInterface $em, $id){

        if($this->getUser()){
            $run = $em->getRepository(Run::class)->find($id);

            $run->setPlaces($run->getPlaces()-1);
            $run->addPasenger($this->getUser());
            $em->persist($run);
            $em->flush();
            return $this->redirectToRoute('listRuns');
        }
    }

    public function notationMember($id, Request $request, EntityManagerInterface $em){
        //créer un formulaire de comment vide
        $comment = new Comment();

        //et l'associe au film
        $comment->setTarget($target);

        $user = $this->getUser();

        //si l'utilisateur est connecté alors ont prend ses informations sinon non
        if($user)
        {
            $comment->setEmail($user->getEmail());
        }
        //créer un formulaire en lui associant notre comment vide
        $commentForm = $this->createForm(CommentType::class, $comment, ['user'=>$this->getUser()]);

        //prend les données envoyées et les injectent dans $comment
        $commentForm->handleRequest(($request));


        //si le formulaire est soumis...
        if($commentForm->isSubmitted() && $commentForm->isValid()){


            //sauvegarde l'entité en base de données
            $em->persist($comment);
            $em->flush();

            //stocke un message en session pour affichage sur la page suivante
            $this->addFlash("success", "Your comment has been published!");
            //rediriger l'utilisateur ici-même pour vider le formulaire
            return $this->redirectToRoute('detailRun', ["id" => $id]);
        }

        return $this->render('run/detail.html.twig', [
            "run" => $run,
            "commentForm" => $commentForm->createView()
        ]);
    }
}
