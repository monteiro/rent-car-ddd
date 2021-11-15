<?php
namespace App\RentCar\Infrastructure\Symfony\Console;

use App\RentCar\Application\Car\CreateCarCommand;
use App\RentCar\Application\Customer\CreateCustomerCommand;
use App\RentCar\Application\Reservation\CancelReservationCommand;
use App\RentCar\Application\Reservation\CreateReservationCommand;
use App\RentCar\Domain\Model\Customer\CustomerRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

final class RentCarSimulationCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'app:rentcar:simulation';
    private MessageBusInterface $messageBus;
    private CustomerRepository $customerRepository;

    public function __construct(
        MessageBusInterface $messageBus
    ) {
        parent::__construct(self::$defaultName);
        $this->messageBus = $messageBus;
    }

    protected function configure(): void
    {
        $this->setDescription('simulate rentcar startup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $systemUser = Uuid::v4()->toRfc4122();
        
        $this->messageBus->dispatch(
            new CreateCarCommand(
                'mitsubishi',
                'lancer',
                'standard',
                $systemUser
            )
        );
        
        $envelope = $this->messageBus->dispatch(
            new CreateCustomerCommand(
                'John Doe',
                '44 Grange Road London',
                '+4429129891',
                'johndoe@test.com',
                $systemUser
            )
        );

        $handledStamp = $envelope->last(HandledStamp::class);
        $customerId = $handledStamp->getResult();
        
        $envelope = $this->messageBus->dispatch(
            new CreateReservationCommand(
                '21 Main Road London',
                new \DateTimeImmutable('2021-12-01 14:00:00'),
                new \DateTimeImmutable('2021-12-05 14:00:00'),
                'premium',
                $customerId,
                $systemUser
            )
        );

        $handledStamp = $envelope->last(HandledStamp::class);
        $reservationId = $handledStamp->getResult();

        $this->messageBus->dispatch(
            new CancelReservationCommand(
                $reservationId,
                $systemUser
            )
        );

        return Command::SUCCESS;
    }
    
}