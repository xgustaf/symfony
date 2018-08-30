<?php

namespace App\Controller\Admin;

use App\Entity\Todo;
use App\Form\TodoType;
use App\Repository\TodoRepository;
use App\Utils\Slugger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/todo")
 * @Security("has_role('ROLE_ADMIN')")
 *
 * @author Robert Razniewski <xgustaf@gmail.com>
 */
class TodoController extends AbstractController
{
    /**
     * Lists all Todos.
     *
     * @Route("/", methods={"GET"}, name="admin_todo_index")
     */
    public function index(TodoRepository $todos): Response
    {
        $authorTodos = $todos->findBy(['author' => $this->getUser()]);

        return $this->render('admin/todo/index.html.twig', ['todos' => $authorTodos]);
    }

    /**
     * Creates a new Todo entity.
     *
     * @Route("/new", methods={"GET", "POST"}, name="admin_todo_new")
     *
     */
    public function new(Request $request): Response
    {
        $todo = new Todo();
        $todo->setAuthor($this->getUser());

        // See https://symfony.com/doc/current/book/forms.html#submitting-forms-with-multiple-buttons
        $form = $this->createForm(TodoType::class, $todo);

        $form->handleRequest($request);

        // the isSubmitted() method is completely optional because the other
        // isValid() method already checks whether the form is submitted.
        // However, we explicitly add it to improve code readability.
        // See https://symfony.com/doc/current/best_practices/forms.html#handling-form-submits
        if ($form->isSubmitted() && $form->isValid()) {
            $todo->setSlug(Slugger::slugify($todo->getTitle()));

            $em = $this->getDoctrine()->getManager();
            $em->persist($todo);
            $em->flush();

            // Flash messages are used to notify the user about the result of the
            // actions. They are deleted automatically from the session as soon
            // as they are accessed.
            // See https://symfony.com/doc/current/book/controller.html#flash-messages
            $this->addFlash('success', 'todo.created_successfully');

            return $this->redirectToRoute('admin_todo_index');
        }

        return $this->render('admin/todo/new.html.twig', [
            'post' => $todo,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays a form to edit an existing Todo entity.
     *
     * @Route("/{id<\d+>}/edit",methods={"GET", "POST"}, name="admin_todo_edit")
     */
    public function edit(Request $request, Todo $todo): Response
    {
        $form = $this->createForm(TodoType::class, $todo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $todo->setSlug(Slugger::slugify($todo->getTitle()));
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'todo.updated_successfully');

            return $this->redirectToRoute('admin_todo_index');
        }

        return $this->render('admin/todo/edit.html.twig', [
            'todo' => $todo,
            'form' => $form->createView(),
        ]);
    }
}
