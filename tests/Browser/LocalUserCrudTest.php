<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * CRUD completo de usuários locais (/local) + login local.
 */
class LocalUserCrudTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function loginAdmin(Browser $browser)
    {
        $browser->visit('/')
            ->clickLink('Entrar')
            ->waitFor('#loginUsuario')
            ->typeSlowly('loginUsuario', '1111')
            ->press('Login')
            ->waitForText('Impressoras', 10);
    }

    public function test_crud_usuario_local()
    {
        $this->browse(function (Browser $browser) {
            $this->loginAdmin($browser);

            // CREATE
            $browser->visit('/local')
                ->waitForText('Usuários locais')
                ->clickLink('Adicionar usuário local')
                ->waitFor('input[name=name]')
                ->type('name', 'Usuário Local Teste')
                ->type('codpes', 'usuario.local')
                ->type('email', 'local@teste.com')
                ->type('password', 'secret123')
                ->press('Enviar')
                ->waitForLocation('/local')
                ->assertSee('Usuário Local Teste')
                ->assertSee('usuario.local');

            $user = User::where('codpes', 'usuario.local')->firstOrFail();

            // validação: email inválido e senha curta devem recusar
            $browser->visit('/local/create')
                ->type('name', 'X')
                ->type('codpes', 'x')
                ->type('email', 'nao-e-email')
                ->type('password', '123')
                ->press('Enviar')
                ->waitFor('.alert-danger')
                ->assertPresent('.alert-danger');

            // UPDATE (inclui troca de senha, pois o update exige password)
            $browser->visit("/local/{$user->id}/edit")
                ->waitFor('input[name=name]')
                ->clear('name')
                ->type('name', 'Usuário Local Editado')
                ->clear('password')
                ->type('password', 'novasenha123')
                ->press('Enviar')
                ->waitForLocation('/local')
                ->assertSee('Usuário Local Editado');
            
            // desloga do admin para testar o login local
            $browser->clickLink('Sair')
                ->waitForLink('Entrar', 10);

            // erro
            $browser->visit('/login/local')
                ->type('codpes', 'usuario.local')
                ->type('password', 'errada')
                ->press('Login')
                ->waitForText('Usuário e senha incorretos')
                ->assertSee('Usuário e senha incorretos');

            // sucesso
            $browser->waitFor('input[name=codpes]')
                ->type('codpes', 'usuario.local')
                ->type('password', 'novasenha123')
                ->press('Login')
                ->waitForText('Impressoras', 10);
            
            // Loga como adm para excluir uário local
            $browser->clickLink('Sair')
                ->waitForLink('Entrar', 10)
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('loginUsuario', '1111')
                ->press('Login')
                ->waitForText('Impressoras', 10);

            // DELETE
            $browser->visit('/local')
                ->waitForText('Usuário Local Editado')
                ->click('#actions button[type=submit]')
                ->acceptDialog()
                ->waitUntilMissingText('Usuário Local Editado')
                ->assertDontSee('Usuário Local Editado');
        });
    }
}
