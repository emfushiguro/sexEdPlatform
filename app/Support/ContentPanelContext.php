<?php

namespace App\Support;

use Illuminate\Http\Request;

class ContentPanelContext
{
    public function __construct(
        private readonly string $panel,
    ) {
    }

    public static function fromRequest(?Request $request): self
    {
        $routeName = $request?->route()?->getName();

        return self::fromRouteName(is_string($routeName) ? $routeName : null);
    }

    public static function fromRouteName(?string $routeName): self
    {
        $panel = is_string($routeName) && str_starts_with($routeName, 'admin.')
            ? 'admin'
            : 'instructor';

        return new self($panel);
    }

    public function panel(): string
    {
        return $this->panel;
    }

    public function routePrefix(): string
    {
        return $this->panel;
    }

    public function layout(): string
    {
        return $this->isAdmin() ? 'layouts.admin' : 'layouts.instructor-app';
    }

    public function name(string $suffix): string
    {
        return $this->routePrefix() . '.' . ltrim($suffix, '.');
    }

    public function isAdmin(): bool
    {
        return $this->panel === 'admin';
    }

    public function isInstructor(): bool
    {
        return $this->panel === 'instructor';
    }
}
