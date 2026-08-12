<?php

test('the application entry point redirects to the API documentation', function () {
    $this->get('/')
        ->assertRedirect('/docs');
});
