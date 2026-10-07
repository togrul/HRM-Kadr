<?php

use App\Support\PositionLevel;

it('guesses the seniority band from the position name', function (string $name, int $level): void {
    expect(PositionLevel::guess($name))->toBe($level);
})->with([
    ['Direktor', 1],
    ['Direktor müavini (texniki/əməliyyat məsələləri üzrə)', 2],
    ['Direktorun müşaviri', 3],
    ['Baş mühəndis', 3],
    ['Şöbə rəisi', 4],
    ['Şöbə rəisi – baş mühasib', 4],
    ['Menecer (keyfiyyətə nəzarət üzrə)', 5],
    ['Böyük mühəndis (ərazilərdə ümumi tikinti işləri üzrə)', 5],
    ['Aparıcı mühəndis (əməyin mühafizəsi və texniki təhlükəsizlik üzrə)', 5],
    ['Kargüzarlıq üzrə aparıcı mütəxəssis', 5],
    ['İnformasiya texnologiyaları üzrə aparıcı mütəxəssis', 5],
    ['Böyük mühəndis topoqraf (ərazilərdə)', 5],
    ['Kargüzarlıq üzrə mütəxəssis', 6],
    ['Kadrlar üzrə mütəxəssis', 6],
    ['Hüquqşünas', 6],
    ['Mühasib', 6],
    ['Mühəndis (ümum tikinti işləri üzrə)', 6],
    ['Sürücü', 7],
    ['Xadimə', 7],
]);
