<?php

test('redirects from home', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
