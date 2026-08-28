<?php

namespace Database\Seeders;

use App\Models\Experience;
use App\Models\Project;
use App\Models\SiteSetting;
use App\Models\SkillGroup;
use App\Models\SkillItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * The starting content for the public site. Everything here is editable from
 * the admin panel afterwards; this only gives a fresh install something real
 * to render.
 *
 * Re-running the seeder refreshes the singleton settings row but leaves the
 * project, experience and skill lists alone once they exist, so seeding twice
 * never duplicates them or overwrites edits.
 */
class SiteContentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the site settings, projects, experience and skills.
     */
    public function run(): void
    {
        $this->seedSettings();
        $this->seedProjects();
        $this->seedExperiences();
        $this->seedSkills();
    }

    /**
     * Fill the singleton settings row with the launch copy.
     */
    private function seedSettings(): void
    {
        SiteSetting::current()->fill([
            'profile_name' => 'Héctor Zamorano',
            'profile_initials' => 'HZ',
            'profile_year' => 2026,
            'photo_path' => $this->seededPhotoPath(),

            'hero_kicker' => [
                'es' => 'Ingeniero en Informática · Santiago de Chile',
                'en' => 'Software Engineer · Santiago, Chile',
            ],
            'hero_title' => [
                'es' => 'Software claro, sistemas que duran.',
                'en' => 'Clear software, systems that last.',
            ],
            'hero_lead' => [
                'es' => 'Soy Héctor Zamorano. Diseño y construyo aplicaciones web, APIs e infraestructura de datos para salud, educación y retail. También enseño: publico cursos y guías técnicas para desarrolladores de habla hispana.',
                'en' => "I'm Héctor Zamorano. I design and build web applications, APIs and data infrastructure for health, education and retail. I also teach: I publish courses and technical guides for Spanish-speaking developers.",
            ],

            'about_title' => ['es' => 'Sobre mí', 'en' => 'About me'],
            'about_body' => [
                'es' => "Soy ingeniero en informática con más de diez años construyendo software para salud, educación y retail. Me interesa el tramo completo: entender el problema, diseñar la arquitectura, escribir el código y dejarlo funcionando en producción.\n\nDesde 2019 también enseño. Publico cursos y guías técnicas en español porque creo que la mejor documentación es la que no hay que traducir. Cuando no estoy frente al teclado, ando en bicicleta por los cerros de Santiago.",
                'en' => "I'm a software engineer with over ten years building software for health, education and retail. I care about the whole span: understanding the problem, designing the architecture, writing the code and keeping it running in production.\n\nSince 2019 I also teach. I publish courses and technical guides in Spanish because the best documentation is the one you don't have to translate. Away from the keyboard, I cycle the hills around Santiago.",
            ],

            'projects_title' => ['es' => 'Proyectos destacados', 'en' => 'Featured projects'],
            'projects_intro' => [
                'es' => 'Una selección de sistemas en producción, construidos junto a equipos de salud, educación, logística y retail.',
                'en' => 'A selection of production systems, built with teams in health, education, logistics and retail.',
            ],
            'experience_title' => ['es' => 'Experiencia profesional', 'en' => 'Professional experience'],
            'skills_title' => ['es' => 'Habilidades técnicas', 'en' => 'Technical skills'],
            'courses_title' => ['es' => 'Cursos publicados', 'en' => 'Published courses'],
            'courses_intro' => [
                'es' => 'Cursos en español, con ejercicios reales y soporte directo. Publicados de forma independiente.',
                'en' => 'Courses in Spanish, with real exercises and direct support. Independently published.',
            ],
            'guides_title' => ['es' => 'Guías técnicas', 'en' => 'Technical guides'],
            'guides_intro' => [
                'es' => 'Notas técnicas largas: lo que me habría gustado leer antes de empezar cada proyecto.',
                'en' => 'Long-form technical notes: what I wish I had read before starting each project.',
            ],
            'contact_title' => ['es' => 'Contacto', 'en' => 'Contact'],
            'contact_intro' => [
                'es' => '¿Tienes un proyecto, una consulta técnica o quieres proponer una colaboración? Escríbeme: respondo dentro de 48 horas hábiles.',
                'en' => 'Have a project, a technical question or a collaboration in mind? Write to me — I reply within 48 business hours.',
            ],

            'contact_email' => 'hola@hectorzamorano.dev',
            'contact_location' => ['es' => 'Santiago, Chile', 'en' => 'Santiago, Chile'],
            'github_url' => 'https://github.com/hzamorano',
            'github_label' => '@hzamorano',
            'linkedin_url' => 'https://linkedin.com/in/hectorzamorano',
            'linkedin_label' => '/in/hectorzamorano',

            'footer_tagline' => ['es' => 'Hecho en Santiago de Chile.', 'en' => 'Made in Santiago, Chile.'],
            'seo_title' => [
                'es' => 'Héctor Zamorano — Ingeniero en Informática',
                'en' => 'Héctor Zamorano — Software Engineer',
            ],
            'seo_description' => [
                'es' => 'Portafolio de Héctor Zamorano: aplicaciones web, APIs e infraestructura de datos para salud, educación y retail. Cursos y guías técnicas en español.',
                'en' => 'Portfolio of Héctor Zamorano: web applications, APIs and data infrastructure for health, education and retail. Courses and technical guides in Spanish.',
            ],
        ])->save();
    }

    /**
     * The portrait shipped with the design, if it is still on the public disk.
     * A missing file simply leaves the hero showing the initials instead.
     */
    private function seededPhotoPath(): ?string
    {
        $path = 'portfolio/site/hero-photo.webp';

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /**
     * Seed the project rail, keeping any projects already created.
     */
    private function seedProjects(): void
    {
        if (Project::query()->exists()) {
            return;
        }

        $projects = [
            [
                'year' => '2025—2026',
                'type' => ['es' => 'Plataforma web', 'en' => 'Web platform'],
                'name' => 'SIGA — Gestión Académica',
                'description' => [
                    'es' => 'Sistema de matrícula, notas y reportes para una universidad con 12.000 estudiantes. Redujo en 60% el tiempo de cierre de semestre.',
                    'en' => 'Enrollment, grading and reporting system for a 12,000-student university. Cut semester-close time by 60%.',
                ],
                'stack' => ['React', 'Node.js', 'PostgreSQL'],
            ],
            [
                'year' => '2024',
                'type' => ['es' => 'API · Salud', 'en' => 'API · Health'],
                'name' => 'FHIR Gateway',
                'description' => [
                    'es' => 'Capa de interoperabilidad clínica sobre HL7 FHIR que conecta tres sistemas hospitalarios heredados.',
                    'en' => 'Clinical interoperability layer on HL7 FHIR connecting three legacy hospital systems.',
                ],
                'stack' => ['Node.js', 'FHIR', 'Redis'],
            ],
            [
                'year' => '2024',
                'type' => ['es' => 'E-learning', 'en' => 'E-learning'],
                'name' => 'Aula Abierta',
                'description' => [
                    'es' => 'LMS ligero para institutos técnicos: cursos, evaluaciones y certificados, pensado para conexiones lentas.',
                    'en' => 'Lightweight LMS for technical institutes: courses, assessments and certificates, built for slow connections.',
                ],
                'stack' => ['Vue', 'Django', 'S3'],
            ],
            [
                'year' => '2023',
                'type' => ['es' => 'Tiempo real', 'en' => 'Real-time'],
                'name' => 'TrackSur',
                'description' => [
                    'es' => 'Panel logístico en tiempo real para una flota de 240 camiones en el sur de Chile.',
                    'en' => 'Real-time logistics dashboard for a 240-truck fleet in southern Chile.',
                ],
                'stack' => ['React', 'WebSockets', 'TimescaleDB'],
            ],
            [
                'year' => '2022',
                'type' => ['es' => 'App móvil', 'en' => 'Mobile app'],
                'name' => 'StockGo',
                'description' => [
                    'es' => 'Inventario y ventas para pymes con modo offline y sincronización automática.',
                    'en' => 'Inventory and sales for small businesses with offline mode and automatic sync.',
                ],
                'stack' => ['Flutter', 'Firebase'],
            ],
            [
                'year' => '2021',
                'type' => ['es' => 'Datos · BI', 'en' => 'Data · BI'],
                'name' => 'Retail Insight',
                'description' => [
                    'es' => 'Pipeline ETL y tableros de venta diaria para una cadena de retail con 38 tiendas.',
                    'en' => 'ETL pipeline and daily-sales dashboards for a 38-store retail chain.',
                ],
                'stack' => ['Python', 'Airflow', 'Power BI'],
            ],
        ];

        foreach ($projects as $position => $project) {
            Project::query()->create([...$project, 'position' => $position, 'is_published' => true]);
        }
    }

    /**
     * Seed the experience timeline, keeping any roles already created.
     */
    private function seedExperiences(): void
    {
        if (Experience::query()->exists()) {
            return;
        }

        $roles = [
            [
                'period_from' => '2021',
                'period_to' => null,
                'role' => ['es' => 'Ingeniero de software senior', 'en' => 'Senior Software Engineer'],
                'company' => 'Nubetec SpA · Santiago',
                'description' => [
                    'es' => 'Lidero un equipo de cinco personas que construye plataformas web para salud y educación: arquitectura, revisión de código y relación directa con clientes.',
                    'en' => 'I lead a five-person team building web platforms for health and education: architecture, code review and direct client relations.',
                ],
            ],
            [
                'period_from' => '2019',
                'period_to' => null,
                'role' => ['es' => 'Docente de programación (part-time)', 'en' => 'Programming instructor (part-time)'],
                'company' => 'Instituto Profesional IACC · Online',
                'description' => [
                    'es' => 'Cursos de desarrollo web y bases de datos. Más de 3.000 estudiantes entre aulas y cursos en línea.',
                    'en' => 'Web development and database courses. Over 3,000 students across classrooms and online courses.',
                ],
            ],
            [
                'period_from' => '2018',
                'period_to' => '2021',
                'role' => ['es' => 'Desarrollador full-stack', 'en' => 'Full-stack developer'],
                'company' => 'Clínica del Valle · Rancagua',
                'description' => [
                    'es' => 'Sistemas internos de agenda, ficha clínica e integraciones con laboratorio.',
                    'en' => 'Internal scheduling, clinical-record systems and lab integrations.',
                ],
            ],
            [
                'period_from' => '2015',
                'period_to' => '2018',
                'role' => ['es' => 'Analista programador', 'en' => 'Programmer analyst'],
                'company' => 'SGT Consultores · Santiago',
                'description' => [
                    'es' => 'Desarrollo y mantención de sistemas de gestión documental para organismos públicos.',
                    'en' => 'Development and maintenance of document-management systems for public agencies.',
                ],
            ],
        ];

        foreach ($roles as $position => $role) {
            Experience::query()->create([...$role, 'position' => $position, 'is_published' => true]);
        }
    }

    /**
     * Seed the skill meters, keeping any groups already created.
     */
    private function seedSkills(): void
    {
        if (SkillGroup::query()->exists()) {
            return;
        }

        $groups = [
            [
                'name' => ['es' => 'Lenguajes', 'en' => 'Languages'],
                'items' => [
                    ['JavaScript / TypeScript', 95],
                    ['Python', 85],
                    ['SQL', 90],
                    ['Dart', 70],
                ],
            ],
            [
                'name' => ['es' => 'Frontend', 'en' => 'Frontend'],
                'items' => [
                    ['React', 95],
                    ['Vue', 75],
                    ['HTML / CSS', 90],
                    ['Flutter', 70],
                ],
            ],
            [
                'name' => ['es' => 'Backend', 'en' => 'Backend'],
                'items' => [
                    ['Node.js / Express', 90],
                    ['Django', 80],
                    ['PostgreSQL', 85],
                    ['Redis', 75],
                ],
            ],
            [
                'name' => ['es' => 'DevOps y datos', 'en' => 'DevOps & Data'],
                'items' => [
                    ['Docker', 85],
                    ['AWS', 75],
                    ['CI/CD', 80],
                    ['Airflow / Power BI', 70],
                ],
            ],
        ];

        foreach ($groups as $position => $group) {
            $model = SkillGroup::query()->create([
                'name' => $group['name'],
                'position' => $position,
            ]);

            foreach ($group['items'] as $itemPosition => [$name, $percent]) {
                SkillItem::query()->create([
                    'skill_group_id' => $model->id,
                    'name' => $name,
                    'percent' => $percent,
                    'position' => $itemPosition,
                ]);
            }
        }
    }
}
