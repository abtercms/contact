<?php

declare(strict_types=1);

namespace AbterPhp\Contact\Service\Execute;

use AbterPhp\Contact\Domain\Entities\Form;
use AbterPhp\Contact\Domain\Entities\Message as Entity;
use AbterPhp\Contact\Orm\FormRepo as GridRepo;
use AbterPhp\Contact\Validation\Factory\Message as ValidatorFactory;
use AbterPhp\Framework\Domain\Entities\IStringerEntity;
use AbterPhp\Framework\Email\Sender;
use AbterPhp\Framework\I18n\ITranslator;
use Opulence\Events\Dispatchers\IEventDispatcher;
use Opulence\Orm\OrmException;
use Opulence\Validation\IValidator;
use Opulence\Validation\Rules\Errors\ErrorCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    /** @var Message - System Under Test */
    protected $sut;

    /** @var GridRepo|MockObject */
    protected $gridRepoMock;

    /** @var ValidatorFactory|MockObject */
    protected $validatorFactoryMock;

    /** @var IEventDispatcher|MockObject */
    protected $eventDispatcherMock;

    /** @var Sender|MockObject */
    protected $senderMock;

    /** @var ITranslator|MockObject */
    protected $translatorMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->gridRepoMock         = $this->createMock(GridRepo::class);
        $this->validatorFactoryMock = $this->createMock(ValidatorFactory::class);
        $this->eventDispatcherMock  = $this->createMock(IEventDispatcher::class);
        $this->senderMock           = $this->createMock(Sender::class);
        $this->translatorMock       = $this->createMock(ITranslator::class);

        $this->sut = new Message(
            $this->gridRepoMock,
            $this->validatorFactoryMock,
            $this->eventDispatcherMock,
            $this->senderMock,
            $this->translatorMock
        );
    }

    public function testCreateEntity()
    {
        $id = 'foo';

        $actualResult = $this->sut->createEntity($id);

        $this->assertInstanceOf(Entity::class, $actualResult);
        $this->assertSame($id, $actualResult->getId());
    }

    public function testGetFormGetsFormByIdentifierByDefault()
    {
        $formIdentifier = 'foo';

        $formStub = $this->createMock(Form::class);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willReturn($formStub);
        $this->gridRepoMock->expects($this->never())->method('getById');

        /** @var Form $actualResult */
        $actualResult = $this->sut->getForm($formIdentifier);

        $this->assertSame($formStub, $actualResult);
    }

    public function testGetFormGetsFormByIdentifierFromCacheTheSecondTime()
    {
        $formIdentifier = 'foo';

        $formStub = $this->createMock(Form::class);
        $formStub->expects($this->any())->method('getIdentifier')->willReturn($formIdentifier);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willReturn($formStub);
        $this->gridRepoMock->expects($this->never())->method('getById');

        /** @var Form $actualResult */
        $actualResult1 = $this->sut->getForm($formIdentifier);
        $actualResult2 = $this->sut->getForm($formIdentifier);

        $this->assertSame($actualResult1, $actualResult2);
        $this->assertSame($formStub, $actualResult1);
    }

    public function testGetFormGetsFormByIdByIfByIdentifierNothingFound()
    {
        $formIdentifier = 'foo';

        $formStub = $this->createMock(Form::class);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willThrowException(new OrmException());
        $this->gridRepoMock->expects($this->once())->method('getById')->willReturn($formStub);

        /** @var Form $actualResult */
        $actualResult = $this->sut->getForm($formIdentifier);

        $this->assertSame($formStub, $actualResult);
    }

    public function testGetFormCanReturnNull()
    {
        $formIdentifier = 'foo';

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willThrowException(new OrmException());
        $this->gridRepoMock->expects($this->once())->method('getById')->willThrowException(new OrmException());

        /** @var Form $actualResult */
        $actualResult = $this->sut->getForm($formIdentifier);

        $this->assertNull($actualResult);
    }

    public function testSend()
    {
        $expectedResult = 13;

        /** @var Entity|MockObject $entityStub */
        $entityStub = $this->createMock(Entity::class);

        $this->senderMock
            ->expects($this->once())
            ->method('send')
            ->willReturn($expectedResult);

        /** @var Form $actualResult */
        $actualResult = $this->sut->send($entityStub);

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testGetFailedRecipients()
    {
        $expectedResult = ['foo' => 'bar'];

        $this->senderMock
            ->expects($this->once())
            ->method('getFailedRecipients')
            ->willReturn($expectedResult);

        /** @var Form $actualResult */
        $actualResult = $this->sut->getFailedRecipients();

        $this->assertSame($expectedResult, $actualResult);
    }

    public function testValidateFormReturnsEmptyArrayIfValid()
    {
        $formIdentifier = 'foo';
        $maxBodyLength  = 128;

        $postData = [
            'foo' => 'bar',
        ];

        $formStub = $this->createMock(Form::class);
        $formStub->expects($this->any())->method('getMaxBodyLength')->willReturn($maxBodyLength);

        $this->gridRepoMock->expects($this->any())->method('getByIdentifier')->willReturn($formStub);

        $validatorMock = $this->createMock(IValidator::class);
        $validatorMock->expects($this->any())->method('isValid')->willReturn(true);

        $this->validatorFactoryMock->expects($this->any())->method('setMaxBodyLength')->willReturnSelf();
        $this->validatorFactoryMock->expects($this->any())->method('createValidator')->willReturn($validatorMock);

        /** @var Form $actualResult */
        $actualResult = $this->sut->validateForm($formIdentifier, $postData);

        $this->assertSame([], $actualResult);
    }

    public function testValidateFormReturnsErrorsIfAny()
    {
        $formIdentifier = 'foo';
        $maxBodyLength  = 128;
        $errors         = [
            'bar' => 'baz',
        ];

        $postData = [
            'foo' => 'bar',
        ];

        $formStub = $this->createMock(Form::class);
        $formStub->expects($this->any())->method('getMaxBodyLength')->willReturn($maxBodyLength);

        $this->gridRepoMock->expects($this->any())->method('getByIdentifier')->willReturn($formStub);

        $errorsMock = $this->createMock(ErrorCollection::class);
        $errorsMock->expects($this->any())->method('getAll')->willReturn($errors);

        $validatorMock = $this->createMock(IValidator::class);
        $validatorMock->expects($this->any())->method('isValid')->willReturn(false);
        $validatorMock->expects($this->any())->method('getErrors')->willReturn($errorsMock);

        $this->validatorFactoryMock->expects($this->any())->method('setMaxBodyLength')->willReturnSelf();
        $this->validatorFactoryMock->expects($this->any())->method('createValidator')->willReturn($validatorMock);

        /** @var Form $actualResult */
        $actualResult = $this->sut->validateForm($formIdentifier, $postData);

        $this->assertSame($errors, $actualResult);
    }

    public function testValidateFormSetsMaxBodyLength()
    {
        $formIdentifier = 'foo';
        $maxBodyLength  = 128;

        $postData = [
            'foo' => 'bar',
        ];

        $formStub = $this->createMock(Form::class);
        $formStub->expects($this->any())->method('getMaxBodyLength')->willReturn($maxBodyLength);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willReturn($formStub);

        $validatorMock = $this->createMock(IValidator::class);
        $validatorMock
            ->expects($this->once())
            ->method('isValid')
            ->with($postData)
            ->willReturn(true);

        $this->validatorFactoryMock
            ->expects($this->once())
            ->method('setMaxBodyLength')
            ->with($maxBodyLength)
            ->willReturnSelf();
        $this->validatorFactoryMock
            ->expects($this->once())
            ->method('createValidator')
            ->willReturn($validatorMock);

        /** @var Form $actualResult */
        $actualResult = $this->sut->validateForm($formIdentifier, $postData);

        $this->assertSame([], $actualResult);
    }

    public function testValidateThrowsExceptionIfEntityIsNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);

        $formIdentifier = 'foo';
        $maxBodyLength  = 128;

        $postData = [
            'foo' => 'bar',
        ];

        $formStub = $this->createMock(Form::class);
        $formStub->expects($this->any())->method('getMaxBodyLength')->willReturn($maxBodyLength);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willThrowException(new OrmException());
        $this->gridRepoMock->expects($this->once())->method('getById')->willThrowException(new OrmException());

        $validatorMock = $this->createMock(IValidator::class);
        $validatorMock->expects($this->never())->method('isValid');

        $this->sut->validateForm($formIdentifier, $postData);
    }

    public function testFillEntityThrowsExceptionOnWrongEntity()
    {
        $this->expectException(\InvalidArgumentException::class);

        $formIdentifier = 'foo';
        $entityMock     = $this->createMock(IStringerEntity::class);
        $postData       = [];

        $this->sut->fillEntity($formIdentifier, $entityMock, $postData, []);
    }

    public function testFillEntityDoesNotChangeEntityIfFormIsNotFound()
    {
        $formIdentifier = 'foo';
        $entityMock     = $this->createMock(Entity::class);
        $postData       = [];

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willThrowException(new OrmException());
        $this->gridRepoMock->expects($this->once())->method('getById')->willThrowException(new OrmException());

        $entityMock->expects($this->never())->method('setForm');

        $this->sut->fillEntity($formIdentifier, $entityMock, $postData, []);
    }

    public function testFillEntity()
    {
        $formIdentifier = 'foo';
        $subject        = 'bar';
        $body           = 'baz';
        $fromName       = 'Qux';
        $fromEmail      = 'qux@example.com';
        $fromPhone      = '32 234 4567';

        $postData = [
            'subject'    => $subject,
            'body'       => $body,
            'from_name'  => $fromName,
            'from_email' => $fromEmail,
            'from_phone' => $fromPhone,
        ];

        $entityMock = $this->createMock(Entity::class);

        $formStub = $this->createMock(Form::class);

        $this->gridRepoMock->expects($this->once())->method('getByIdentifier')->willThrowException(new OrmException());
        $this->gridRepoMock->expects($this->once())->method('getById')->willReturn($formStub);

        $entityMock->expects($this->once())->method('setForm');

        $actualResult = $this->sut->fillEntity($formIdentifier, $entityMock, $postData, []);

        $this->assertSame($entityMock, $actualResult);
    }
}
