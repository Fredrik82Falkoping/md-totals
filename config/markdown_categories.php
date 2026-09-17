<?php

// Standardmappning group_key -> kategorinamn, används som fallback för
// tenants som ännu inte har egna kategorier registrerade i databasen.
// Detta är en GISSNING baserad på vanlig ICA-butiksstruktur - INTE bekräftad
// data från Rainer. Byt ut mot riktig data när/om den blir tillgänglig,
// antingen här eller per tenant i categories-tabellen.

return [
    'defaults' => [
        1 => 'Kolonial',
        2 => 'Bröd',
        3 => 'Frukt & Grönt',
        4 => 'Chark',
        5 => 'Mejeri',
        6 => 'Ost',
        7 => 'Kött',
        8 => 'Fisk',
        9 => 'Sallad Färdigmat',
        10 => 'Kallskänk/Hemlagat',
        11 => 'Kolonial/Nonfood',
        12 => 'Deli',
        13 => 'Blommor',
        14 => 'Bageri',
        15 => 'Fryst',
    ],
];