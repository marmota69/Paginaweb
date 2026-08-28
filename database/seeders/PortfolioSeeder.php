<?php

namespace Database\Seeders;

use App\CourseLevel;
use App\Models\Course;
use App\Models\Guide;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortfolioSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the courses and guides shipped with the portfolio design.
     */
    public function run(): void
    {
        foreach ($this->courses() as $position => $course) {
            Course::query()->updateOrCreate(
                ['slug' => $course['slug']],
                [...$course, 'position' => $position, 'is_published' => true],
            );
        }

        foreach ($this->guides() as $guide) {
            Guide::query()->updateOrCreate(
                ['slug' => $guide['slug']],
                [...$guide, 'is_published' => true],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function courses(): array
    {
        return [
            [
                'slug' => 'js-fundamentos',
                'title' => 'Fundamentos de JavaScript moderno',
                'description' => 'Variables, funciones, asincronía y el DOM: la base del lenguaje explicada desde cero, con ejercicios corregidos.',
                'level' => CourseLevel::Basico,
                'duration' => '12 horas',
                'link' => 'https://cursos.hectorzamorano.dev/js-fundamentos',
            ],
            [
                'slug' => 'node-apis',
                'title' => 'APIs REST con Node.js y Express',
                'description' => 'Diseño de endpoints, middleware, autenticación JWT y despliegue de una API completa en producción.',
                'level' => CourseLevel::Intermedio,
                'duration' => '16 horas',
                'link' => 'https://cursos.hectorzamorano.dev/node-apis',
            ],
            [
                'slug' => 'react-pro',
                'title' => 'React profesional: de cero a producción',
                'description' => 'Componentes, hooks, manejo de estado y patrones de arquitectura para aplicaciones reales.',
                'level' => CourseLevel::Intermedio,
                'duration' => '24 horas',
                'link' => 'https://cursos.hectorzamorano.dev/react-pro',
            ],
            [
                'slug' => 'docker-cicd',
                'title' => 'Docker y despliegue continuo',
                'description' => 'Contenedores, docker-compose y pipelines de CI/CD con GitHub Actions para equipos pequeños.',
                'level' => CourseLevel::Avanzado,
                'duration' => '10 horas',
                'link' => 'https://cursos.hectorzamorano.dev/docker-cicd',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function guides(): array
    {
        return [
            [
                'slug' => 'autenticacion-jwt-node',
                'title' => 'Autenticación JWT en Node.js sin dolores de cabeza',
                'category' => 'Backend',
                'tags' => 'node, seguridad, jwt',
                'published_on' => '2026-05-12',
                'content' => implode("\n", [
                    'La autenticación con tokens es hoy el estándar para APIs. Bien implementada es simple; mal implementada es la fuente de la mitad de los incidentes de seguridad que he revisado.',
                    '## Cómo funciona un JWT',
                    'Un token tiene tres partes: **header**, **payload** y **firma**. Las dos primeras son públicas — cualquiera puede decodificarlas — pero la firma garantiza que nadie las alteró.',
                    '- El servidor firma el token con una clave secreta',
                    '- El cliente lo envía en cada petición, en el header Authorization',
                    '- Nadie puede modificar el payload sin invalidar la firma',
                    '## Implementación mínima',
                    '```',
                    "const token = jwt.sign({ id: user.id }, SECRET, { expiresIn: '2h' });",
                    '```',
                    'Guarda el token en memoria o en una cookie **httpOnly**; evita localStorage para sesiones sensibles. Y define desde el día uno una estrategia de expiración y renovación.',
                ]),
            ],
            [
                'slug' => 'optimizacion-consultas-postgresql',
                'title' => 'Optimización de consultas en PostgreSQL',
                'category' => 'Bases de datos',
                'tags' => 'postgresql, sql, rendimiento',
                'published_on' => '2026-04-03',
                'content' => implode("\n", [
                    'Antes de agregar caché o cambiar de motor, hay que leer el plan de ejecución. El 80% de los problemas de rendimiento que he visto se resuelven con un índice bien elegido.',
                    '## EXPLAIN ANALYZE es tu amigo',
                    'Ejecuta la consulta real y muestra dónde se va el tiempo. Busca **Seq Scan** sobre tablas grandes: casi siempre indica un índice faltante.',
                    '```',
                    'EXPLAIN ANALYZE SELECT * FROM ventas WHERE tienda_id = 12;',
                    '```',
                    '## Tres reglas prácticas',
                    '- Indexa las columnas que aparecen en WHERE y JOIN, no las que aparecen en SELECT',
                    '- Un índice compuesto sirve solo si filtras por su primera columna',
                    '- Mide antes y después: sin números no hay optimización, hay superstición',
                ]),
            ],
            [
                'slug' => 'estado-en-react-usestate',
                'title' => 'Estado en React: cuándo basta useState',
                'category' => 'Frontend',
                'tags' => 'react, hooks, arquitectura',
                'published_on' => '2026-02-19',
                'content' => implode("\n", [
                    'La pregunta más frecuente de mis estudiantes: ¿Redux, Zustand, Context? Mi respuesta casi siempre es la misma: **empieza con useState** y sube de nivel solo cuando duela.',
                    '## Una escalera de tres peldaños',
                    '- Estado local con useState: formularios, toggles, todo lo que vive en un componente',
                    '- Estado compartido con Context: tema, idioma, sesión — datos que cambian poco',
                    '- Una librería externa: solo cuando hay estado global que cambia con frecuencia',
                    '## La señal de alarma',
                    'Si pasas la misma prop por más de tres niveles, no necesitas una librería: probablemente necesitas **reorganizar tus componentes**. La composición resuelve más problemas que el estado global.',
                ]),
            ],
            [
                'slug' => 'ci-cd-github-actions',
                'title' => 'CI/CD con GitHub Actions paso a paso',
                'category' => 'DevOps',
                'tags' => 'ci-cd, github, docker',
                'published_on' => '2026-01-08',
                'content' => implode("\n", [
                    'Un pipeline no tiene que ser complejo para ser útil. Con 30 líneas de YAML puedes pasar de deploys manuales a despliegues automáticos con tests.',
                    '## El pipeline mínimo viable',
                    '- Al abrir un pull request: instala dependencias y corre los tests',
                    '- Al hacer merge a main: construye la imagen Docker y publícala',
                    '- Al etiquetar una versión: despliega a producción',
                    '## Un workflow de ejemplo',
                    '```',
                    'on: { push: { branches: [main] } }',
                    'jobs:',
                    '  test:',
                    '    runs-on: ubuntu-latest',
                    '    steps:',
                    '      - uses: actions/checkout@v4',
                    '      - run: npm ci && npm test',
                    '```',
                    'Empieza por los tests. Un deploy automático sin tests es solo una forma más rápida de romper producción.',
                ]),
            ],
        ];
    }
}
