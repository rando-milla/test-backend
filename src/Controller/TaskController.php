<?php
namespace App\Controller;

use App\Entity\Task;
use App\Entity\User;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class TaskController extends AbstractController
{
    #[Route('/api/tasks/auto-assign', name: 'api_task_auto_assign', methods: ['post'])]
    #[OA\Response(
        response: 200,
        description: 'Assign the task to the current user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Task::class))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Parameter(
        name: 'task_id',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function assignTaskToMe(#[CurrentUser] User $user, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $taskId = $request->getPayload()->get('task_id');
        $task = $entityManager->getRepository(Task::class)->findOneBy(['id' => $taskId]);
        if (!$task) {
            return $this->json('No task found for id ' . $taskId, 404);
        }
        $task->setUser($user);
        $entityManager->persist($task);
        $user->setTasks($task);
        $entityManager->persist($user);

        $entityManager->flush();
        return $this->json([
            'task' => $task,
        ]);
    }

    #[Route('/api/tasks', name: 'api_task_create', methods: ['post'])]
    #[OA\Response(
        response: 200,
        description: 'Create a task',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Task::class))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Parameter(
        name: 'name',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'description',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'deadline',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'assessment',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function createTask(#[CurrentUser] User $currentUser, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $task = new Task();
        $task->setName($request->getPayload()->get('name'));
        $task->setDescription($request->getPayload()->get('description'));
        $task->setDeadline($request->getPayload()->get('deadline') ?? NULL);
        $task->setAssessment($request->getPayload()->get('assessment') ?? NULL);
        $task->setUser($currentUser);
        $task->setStatus(0);

        $entityManager->persist($task);
        $entityManager->flush();
        $currentUser->setTasks($task);
        $entityManager->persist($currentUser);

        $entityManager->flush();

        return $this->json([
            'task' =>$task
        ]);
    }

    #[Route('/api/tasks', name: 'api_tasks_index', methods:['get'] )]
    #[OA\Response(
            response: 200,
            description: 'Get all tasks of current users',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: new Model(type: Task::class))
            )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function index(#[CurrentUser] User $currentUser, EntityManagerInterface $entityManager): JsonResponse
    {
        $tasks = $entityManager
            ->getRepository(Task::class)
            ->findBy(['user'=> $currentUser]);

        return $this->json($tasks);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_show', methods:['get'] )]
    #[OA\Response(
        response: 200,
        description: 'Get single task',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Task::class))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function show(#[CurrentUser] User $currentUser, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {

            return $this->json('No task found for id ' . $id, 404);
        }


        return $this->json($task);
    }

    /**
     * @throws \Exception
     */
    #[Route('/api/tasks/{id}', name: 'api_tasks_update', methods:['put', 'patch'] )]
    #[OA\Response(
        response: 200,
        description: 'Update task',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Task::class))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Parameter(
        name: 'deadline',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'assessment',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function update(#[CurrentUser] User $currentUser, EntityManagerInterface $entityManager, Request $request, int $id): JsonResponse
    {
        $task = $entityManager->getRepository(Task::class)->findOneby(["id" => $id, "user" => $currentUser]);

        if (!$task) {
            return $this->json('No task found for id' . $id, 404);
        }

        $date = new DateTime();
        $date->setTimestamp($request->getPayload()->get('deadline'));
        $date->setTimezone(new DateTimeZone($currentUser->getTimezone()));

        $task->setDeadline($date);
        $task->setAssessment($request->getPayload()->get('assessment'));
        $entityManager->persist($task);
        $entityManager->flush();


        return $this->json($task);
    }

    #[Route('/api/tasks/{id}', name: 'api_tasks_delete', methods:['delete'] )]
    #[OA\Response(
        response: 200,
        description: 'Create a task',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Task::class))
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthorised',
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Tag(name: 'task')]
    #[Security(name: 'Bearer')]
    public function delete(#[CurrentUser] User $currentUser, EntityManagerInterface $entityManager, int $id): JsonResponse
    {
        $task = $entityManager->getRepository(Task::class)->findOneby(["id" => $id, "user" => $currentUser]);

        if (!$task) {
            return $this->json('No task found for id' . $id, 404);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->json('Deleted a task successfully with id ' . $id);
    }
}