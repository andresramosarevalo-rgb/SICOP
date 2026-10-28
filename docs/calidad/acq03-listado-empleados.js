// ACQ-03 · Tiempo de respuesta de GET /nomina/empleados con búsqueda por nombre.
// 10 usuarios virtuales durante 2 minutos, cada uno con su propio usuario (k6-1 a k6-10)
// para no chocar con el límite de 5 inicios de sesión por minuto; umbral: p95 < 2 s.
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = __ENV.BASE_URL || 'http://laravel.test:8001';

export const options = {
    vus: 10,
    duration: '2m',
    // k6 borra las cookies en cada iteración; sin esto solo la primera conserva la sesión.
    noCookiesReset: true,
    thresholds: {
        'http_req_duration{endpoint:listado}': ['p(95)<2000'],
    },
};

function xsrf(jar) {
    const cookie = jar.cookiesForURL(BASE).XSRF_TOKEN || jar.cookiesForURL(BASE)['XSRF-TOKEN'];
    return decodeURIComponent(cookie[0]);
}

export function setup() {
    return { busquedas: ['mar', 'ana', 'lui', 'car', 'jo'] };
}

export default function (datos) {
    const jar = http.cookieJar();

    if (__ITER === 0) {
        http.get(`${BASE}/login`);
        const login = http.post(
            `${BASE}/login`,
            { email: `k6-${__VU}@sicop.test`, password: 'secreto-k6' },
            { headers: { 'X-XSRF-TOKEN': xsrf(jar) }, redirects: 0 },
        );
        check(login, { 'login redirige': (r) => r.status === 302 });
    }

    const termino = datos.busquedas[__ITER % datos.busquedas.length];
    const respuesta = http.get(`${BASE}/nomina/empleados?buscar=${termino}`, {
        tags: { endpoint: 'listado' },
    });
    check(respuesta, {
        'listado 200': (r) => r.status === 200,
        // Inertia escapa las barras en el JSON de la página (Nomina\/Empleados\/Index).
        'es el listado de empleados': (r) => r.url.includes('/nomina/empleados') && /Nomina\\?\/Empleados\\?\/Index/.test(r.body),
    });
    sleep(1);
}
