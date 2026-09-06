<?php

it('fake embedding generation', function () {
   \Laravel\Ai\Embeddings::fake([
       [[.1, .2, .3]]
   ])->preventStrayEmbeddings();

   $embedding = \Laravel\Ai\Embeddings::for(['it does not matter'])->generate()->first();

   expect($embedding)->toBe([.1, .2, .3])->toHaveCount(3);
});
