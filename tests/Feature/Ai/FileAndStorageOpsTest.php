<?php

use Laravel\Ai\Files;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Stores;

it('fake provider file and storage', function () {
    Files::fake()->preventStrayOperations();
    Stores::fake()->preventStrayOperations();
    $file = Files::put(Document::fromPath(__DIR__.'/../Fixtures/test.pdf'));

    $store = Stores::create('test-store');
    $added = $store->add($file, ['team_id' => 1, 'user_id' => 1, 'filename' => 'test.pdf']);

    expect($file->id)->toBeString()->not()->toBeEmpty();
    expect($added->id)->toBeString()->not()->toBeEmpty();

});
