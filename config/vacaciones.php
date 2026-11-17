<?php

return [
    'zona_horaria' => 'America/Bogota',

    // Jornada confirmada: lunes a sábado, excluyendo festivos colombianos.
    // Días ISO de la semana (lunes = 1, domingo = 7).
    'dias_habiles' => [1, 2, 3, 4, 5, 6],

    // Fechas no laborables adicionales en formato YYYY-MM-DD.
    'festivos' => [],

    'excluir_festivos_colombianos' => true,

    'es_calendario_confirmado' => true,
];
