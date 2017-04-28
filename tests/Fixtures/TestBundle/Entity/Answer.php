<?php

declare(strict_types=1);
/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * Answer.
 *
 * @ORM\Table(name="answer")
 * @ORM\Entity
 * @ApiResource(collectionOperations={
 *     "get_subresource_answer"={"method"="GET", "normalization_context"={"groups"={"foobar"}}}
 * })
 */
class Answer
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"foobar"})
     */
    private $id;

    /**
     * @ORM\Column(name="content", type="string", nullable=false)
     * @Serializer\Groups({"foobar"})
     */
    private $content;

    /**
     * @ORM\OneToOne(targetEntity="Question", mappedBy="answer")
     * @Serializer\Groups({"foobar"})
     */
    private $question;

    /**
     * Get id.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Answer
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set question.
     *
     * @param Question $question
     *
     * @return Answer
     */
    public function setQuestion(Question $question = null)
    {
        $this->question = $question;

        return $this;
    }

    /**
     * Get question.
     *
     * @return Question
     */
    public function getQuestion()
    {
        return $this->question;
    }
}
