<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }
    public function getCommentCountForPost(int $postId): int
    {
        return $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->andWhere('c.idPost = :postId')
        ->setParameter('postId', $postId)
        ->getQuery()
        ->getSingleScalarResult();
    }
    public function getMostCommentedPosts(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->select('p.id, p.titre, COUNT(c.id) as commentCount')
            ->innerJoin('c.idPost', 'p')
            ->groupBy('p.id')
            ->orderBy('commentCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
