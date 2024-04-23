<?php

namespace Thunk\Verbs\Lifecycle;

use InvalidArgumentException;
use Thunk\Verbs\Testing\BrokerFake;
use Thunk\Verbs\Contracts\StoresEvents;
use Thunk\Verbs\Lifecycle\StateManager;
use Thunk\Verbs\Testing\EventStoreFake;
use Thunk\Verbs\Contracts\BrokersEvents;
use Thunk\Verbs\Contracts\StoresSnapshots;
use Thunk\Verbs\Lifecycle\Queue as EventQueue;

class BrokerBuilder
{
    public string $broker_type = Broker::class;
    public string $event_store = EventStore::class;
    public string $event_queue = EventQueue::class;
    public string $snapshot_store = SnapshotStore::class;
    public string $state_manager = StateManager::class;
    public string $metadata_manager = MetadataManager::class;

    public static function primary(): BrokersEvents
    {
        return (new static)->build();
    }

    public static function standalone(): BrokersEvents
    {
        return (new static)
            ->ofType(StandaloneBroker::class)
            ->withEventStore(EventCollection::class)
            ->withSnapshotStore(SnapshotCollection::class)
            ->build();
    }

    public static function fake(): BrokersEvents
    {
        return (new static)
            ->ofType(BrokerFake::class)
            ->withEventStore(EventStoreFake::class)
            ->build();
    }

    public function ofType(string $broker_type): static
    {
        $this->broker_type = ensure_type($broker_type, BrokersEvents::class);

        return $this;
    }

    public function withEventStore(string $event_store): static
    {
        $this->event_store = ensure_type($event_store, StoresEvents::class);

        return $this;
    }

    public function withEventQueue(string $event_queue): static
    {
        $this->event_queue = ensure_type($event_queue, EventQueue::class);

        return $this;
    }

    public function withSnapshotStore(string $snapshot_store): static
    {
        $this->snapshot_store = ensure_type($snapshot_store, StoresSnapshots::class);

        return $this;
    }

    public function withStateManager(string $state_manager): static
    {
        $this->state_manager = ensure_type($state_manager, StateManager::class);

        return $this;
    }

    public function build(): BrokersEvents
    {
        // @todo - is this bad?
        $dispatcher = app(Dispatcher::class);

        $metadata = new MetadataManager();

        $event_store = new $this->event_store(
            metadata: $metadata
        );

        $event_queue = new $this->event_queue;

        $snapshot_store = new $this->snapshot_store;
        
        $state_manager = new $this->state_manager(
            dispatcher: $dispatcher,
            snapshots: $snapshot_store,
            events: $event_store,
        );


        return new $this->broker_type(
            dispatcher: app(Dispatcher::class),
            metadata: $metadata,
            event_store: $event_store,
            event_queue: $event_queue,
            snapshot_store: $snapshot_store,
            state_manager: $state_manager,
        );
    }
}