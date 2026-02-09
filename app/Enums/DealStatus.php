<?php

namespace App\Enums;

enum DealStatus: string
{
    case None = 'none';
    case Contacted = 'contacted';
    case Responded = 'responded';
    case InProgress = 'in_progress';
    case ClosedWon = 'closed_won';
    case ClosedLost = 'closed_lost';

    public function label(): string
    {
        return match ($this) {
            self::None => 'New Leads',
            self::Contacted => 'Contacted',
            self::Responded => 'Responded',
            self::InProgress => 'In Progress',
            self::ClosedWon => 'Customers',
            self::ClosedLost => 'Lost',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            array_map(fn (self $s) => $s->value, self::cases()),
            array_map(fn (self $s) => $s->label(), self::cases()),
        );
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
