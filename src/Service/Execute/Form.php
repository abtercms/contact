<?php

declare(strict_types=1);

namespace AbterPhp\Contact\Service\Execute;

use AbterPhp\Contact\Domain\Entities\Form as Entity;
use AbterPhp\Contact\Orm\FormRepo as GridRepo;
use AbterPhp\Contact\Validation\Factory\Form as ValidatorFactory;
use AbterPhp\Framework\Domain\Entities\IStringerEntity;
use AbterPhp\Framework\Http\Service\Execute\RepoServiceAbstract;
use Cocur\Slugify\Slugify;
use Opulence\Events\Dispatchers\IEventDispatcher;
use Opulence\Http\Requests\UploadedFile;
use Opulence\Orm\IUnitOfWork;
use Opulence\Orm\OrmException;

class Form extends RepoServiceAbstract
{
    /** @var Slugify */
    protected $slugify;

    /**
     * Form constructor.
     *
     * @param GridRepo         $repo
     * @param ValidatorFactory $validatorFactory
     * @param IUnitOfWork      $unitOfWork
     * @param IEventDispatcher $eventDispatcher
     * @param Slugify          $slugify
     */
    public function __construct(
        GridRepo $repo,
        ValidatorFactory $validatorFactory,
        IUnitOfWork $unitOfWork,
        IEventDispatcher $eventDispatcher,
        Slugify $slugify
    ) {
        parent::__construct($repo, $validatorFactory, $unitOfWork, $eventDispatcher);

        $this->slugify = $slugify;
    }

    /**
     * @param string $entityId
     *
     * @return Entity
     */
    public function createEntity(string $entityId): IStringerEntity
    {
        return new Entity($entityId, '', '', '', '', '', '', 0);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     *
     * @param Entity         $entity
     * @param array          $postData
     * @param UploadedFile[] $fileData
     *
     * @return Entity
     * @throws OrmException
     */
    protected function fillEntity(IStringerEntity $entity, array $postData, array $fileData): IStringerEntity
    {
        $name          = (string)$postData['name'];
        $identifier    = (string)$postData['identifier'];
        $toName        = (string)$postData['to_name'];
        $toEmail       = (string)$postData['to_email'];
        $successUrl    = (string)$postData['success_url'];
        $failureUrl    = (string)$postData['failure_url'];
        $maxBodyLength = (int)$postData['max_body_length'];

        if (!$identifier) {
            $identifier = $this->slugify->slugify($name);
        }

        $entity
            ->setName($name)
            ->setIdentifier($identifier)
            ->setToName($toName)
            ->setToEmail($toEmail)
            ->setSuccessUrl($successUrl)
            ->setFailureUrl($failureUrl)
            ->setMaxBodyLength($maxBodyLength);

        return $entity;
    }
}