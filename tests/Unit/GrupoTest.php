<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Docente;
use App\Models\Estudiante;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    public function test_grupo_contiene_toda_la_informacion_real_asociada(): void
    {
        // 1. Crear el Curso al que pertenece el Grupo
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombreCurso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        // 2. Crear los Docentes (entre 1 y 2 para el grupo, y como consejero)
        $docente1 = Docente::create([
            'codEmpleado' => 1001,
            'nombreEmpleado' => 'Carlos',
            'apellidosEmpleado' => 'Mora Vargas',
        ]);

        $docente2 = Docente::create([
            'codEmpleado' => 1002,
            'nombreEmpleado' => 'Ana',
            'apellidosEmpleado' => 'Castro Solís',
        ]);

        // 3. Instanciar el Grupo con sus atributos reales
        $grupo = Grupo::create([
            'numeroCiclo' => 2,
            'ano' => 2026,
            'numeroGrupo' => 1,
            'abierto' => true,
            'curso_id' => $curso->id,
        ]);

        // Asignar los 2 docentes al grupo (regla: 1..2 docentes)
        $grupo->docentes()->attach([$docente1->id, $docente2->id]);

        // 4. Crear y matricular 15 estudiantes (regla: 15..* estudiantes) con su nota y consejero
        for ($i = 1; $i <= 15; $i++) {
            $estudiante = Estudiante::create([
                'carne' => 'C300' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'nombre' => 'Nombre' . $i,
                'apellidos' => 'Apellido' . $i,
                'consejero_id' => $docente1->id,
            ]);

            $grupo->estudiantes()->attach($estudiante->id, ['nota' => 90.0]);
        }

        // Recargar la instancia con todas sus relaciones
        $grupo->load(['curso', 'docentes', 'estudiantes.consejero']);

        // 5. Verificaciones de que la instancia de Grupo tiene toda la información real
        $this->assertInstanceOf(Grupo::class, $grupo);
        $this->assertEquals(2, $grupo->numeroCiclo);
        $this->assertEquals(2026, $grupo->ano);
        $this->assertEquals(1, $grupo->numeroGrupo);
        $this->assertTrue($grupo->abierto);

        // Verificar Curso asociado (multiplicidad 1)
        $this->assertNotNull($grupo->curso);
        $this->assertEquals('IF6100', $grupo->curso->sigla);

        // Verificar Docentes asociados (multiplicidad 1..2)
        $this->assertGreaterThanOrEqual(1, $grupo->docentes->count());
        $this->assertLessThanOrEqual(2, $grupo->docentes->count());

        // Verificar Estudiantes matriculados (multiplicidad 15..*) y su nota en EstudianteMatriculado
        $this->assertGreaterThanOrEqual(15, $grupo->estudiantes->count());
        $this->assertEquals(90.0, $grupo->estudiantes->first()->pivot->nota);
        $this->assertEquals($docente1->id, $grupo->estudiantes->first()->consejero->id);
    }
}