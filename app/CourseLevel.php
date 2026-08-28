<?php

namespace App;

enum CourseLevel: string
{
    case Basico = 'basico';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    /**
     * The translated label shown to visitors.
     */
    public function label(): string
    {
        return __('portfolio.levels.'.$this->value);
    }

    /**
     * The design-system tag class used to badge this level.
     */
    public function tagClass(): string
    {
        return match ($this) {
            self::Basico => 'tag tag-neutral',
            self::Intermedio => 'tag tag-accent',
            self::Avanzado => 'tag tag-outline',
        };
    }

    /**
     * All levels, in the order the admin form lists them.
     *
     * @return array<int, self>
     */
    public static function ordered(): array
    {
        return [self::Basico, self::Intermedio, self::Avanzado];
    }
}
