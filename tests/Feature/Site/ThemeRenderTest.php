<?php

test('the homepage renders the fixed navy theme on the html element', function (): void {
    $this->get('/')->assertOk()->assertSee('data-theme="navy"', false);
});
