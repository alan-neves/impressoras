<?php

namespace Tests\Browser;

use App\Models\Assistant;
use App\Models\Printer;
use App\Models\Rule;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * CRUD de monitores (/assistants) + verificação do gate "monitor"
 * para um usuário NÃO admin cadastrado como monitor.
 */
class AssistantCrudTest extends DuskTestCase
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

    private function criaRegra()
    {
        return Rule::create([
            'name' => 'Regra Fila',
            'queue_control' => 1,
            'quota_period' => 'Mensal',
            'quota' => 100,
            'quota_type' => 'Páginas',
        ]);
    }

    private function criaImpressora(Rule $rule)
    {
        return Printer::create([
            'name' => 'Impressora Fila',
            'machine_name' => 'ImpressoraTesteDusk001',
            'location' => 'Sala',
            'rule_id' => $rule->id,
            'active' => true,
        ]);
    }

    public function test_crud_assistant()
    {
        $rule = $this->criaRegra();
        $printer = $this->criaImpressora($rule);

        $this->browse(function (Browser $browser) use ($printer,$rule) {
            // login como adm
            $this->loginAdmin($browser);

            // CREATE
            $browser->visit('/assistants')
                ->waitForText('Monitores')
                ->click('#btn-adicionar-codpes')
                ->waitFor('#modal-adicionar-codpes')
                ->waitFor('.select2-search__field')
                ->type('.select2-search__field', '111111')
                ->waitForText('Lucas Andrade Ferreira')
                ->click('.select2-results__option--highlighted, .select2-results__option:first-child')
                ->click('#modal-adicionar-codpes .btn-submit')
                ->waitForText('111111', 10)
                ->assertSee('111111');

            // login como monitor 
            $browser->clickLink('Sair')
                ->waitForLink('Entrar', 10)
                ->visit('/')
                ->clickLink('Entrar')
                ->waitFor('#loginUsuario')
                ->typeSlowly('loginUsuario', '111111')
                ->press('Login')
                ->waitForText('Impressoras', 10);

            // monitor acessa a fila de autorização
            $browser->visit("/printers")
                ->clickLink('Autorização')
                ->waitForLocation("/printers/auth_queue/{$printer->id}")
                ->waitForText('Fila de autorização')
                ->clickLink('Sair');

            $this->loginAdmin($browser); // loga como adm novamente

            // DELETE: monitor local tem botão excluir
            $browser->visit('/assistants')
                ->waitForText('Monitores', 10)
                ->waitForText('111111', 10)
                ->click('#actions button[type=submit]')
                ->acceptDialog()
                ->waitUntilMissingText('111111')
                ->assertDontSee('111111');
        });
    }
}
