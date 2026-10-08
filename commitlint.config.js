/**
 * Convención de commits de SICOP (ESTANDARES.md, sección 2).
 *
 * @type {import('@commitlint/types').UserConfig}
 */
export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'type-enum': [
            2,
            'always',
            [
                'feat',
                'fix',
                'refactor',
                'perf',
                'test',
                'docs',
                'style',
                'build',
                'ci',
                'chore',
            ],
        ],
        'scope-empty': [2, 'never'],
        'scope-case': [2, 'always', 'camel-case'],
        'scope-enum': [
            2,
            'always',
            [
                'pos',
                'cierreCaja',
                'facturacion',
                'contabilidad',
                'planCuentas',
                'comprobantes',
                'nomina',
                'liquidacionNomina',
                'empleados',
                'auth',
                'usuarios',
                'baseDatos',
                'docker',
                'dependencias',
                'ci',
                'estandaresEquipo',
            ],
        ],
        'header-max-length': [2, 'always', 72],
    },
};
