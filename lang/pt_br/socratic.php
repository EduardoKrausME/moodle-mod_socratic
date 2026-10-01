<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings em português do Brasil para mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aiunavailable'] = 'O tutor de IA está temporariamente indisponível. Sua mensagem foi salva e pode ser reenviada para processamento.';
$string['allgroups'] = 'Todos os grupos acessíveis';
$string['allowfinalanswer'] = 'Permitir solicitação de resposta final';
$string['allowhint'] = 'Permitir solicitação de dica';
$string['assistancelabel'] = 'Assistente de aprendizagem por IA';
$string['backtoactivity'] = 'Voltar para a atividade';
$string['completedbylimit'] = 'A conversa foi encerrada porque atingiu o limite de interações.';
$string['completedbystudent'] = 'A conversa foi encerrada pelo estudante.';
$string['completedbyteacher'] = 'A conversa foi marcada como concluída por um professor.';
$string['completioncriterion'] = 'Critério de conclusão';
$string['completioncriterion:ended'] = 'Após a conversa ser encerrada';
$string['completioncriterion:interactions'] = 'Após N interações';
$string['completioncriterion:manual'] = 'Conclusão manual do Moodle';
$string['completioncriterion:teacher'] = 'Após o professor marcar a conversa como concluída';
$string['completiondetail:ended'] = 'Concluir a conversa socrática';
$string['completiondetail:interactions'] = 'Realizar pelo menos {$a} interações com o tutor socrático';
$string['completiondetail:teacher'] = 'Ter a conversa marcada como concluída por um professor';
$string['completioninteractions'] = 'Interações necessárias para conclusão';
$string['completionupdated'] = 'Estado de conclusão atualizado.';
$string['contentbase'] = 'Conteúdo / base de conhecimento';
$string['contentbase_help'] = 'O tutor deve permanecer limitado a este conteúdo. Inclua aqui o material textual que será considerado fonte de verdade da atividade.';
$string['conversation'] = 'Conversa';
$string['conversationended'] = 'Esta conversa foi encerrada.';
$string['conversations'] = 'Conversas dos estudantes';
$string['empty_message'] = 'Digite uma mensagem antes de enviar.';
$string['endconversation'] = 'Encerrar conversa';
$string['eventconversationcompleted'] = 'Conversa socrática concluída';
$string['eventconversationstarted'] = 'Conversa socrática iniciada';
$string['eventmessagesent'] = 'Mensagem socrática enviada';
$string['finalanswer'] = 'Solicitar resposta final';
$string['hint'] = 'Pedir uma dica';
$string['interactioncount'] = '{$a->used} de {$a->max} interações utilizadas';
$string['interactions'] = 'Interações';
$string['invalidaction'] = 'Esta ação não é permitida nesta atividade.';
$string['lastupdated'] = 'Última atualização';
$string['learner'] = 'Estudante';
$string['lockunavailable'] = 'A conversa está processando outra solicitação. Tente novamente.';
$string['markcomplete'] = 'Marcar conversa como concluída';
$string['maxinteractions'] = 'Número máximo de interações';
$string['maxinteractions_help'] = 'Quantidade máxima de turnos do estudante nesta atividade. Pedidos de dica e de resposta final também contam como interação.';
$string['maxinteractionsreached'] = 'O número máximo de interações desta atividade foi atingido.';
$string['messagetutor'] = 'Assistente de aprendizagem por IA';
$string['messageyou'] = 'Você';
$string['modulename'] = 'Tutor socrático';
$string['modulenameplural'] = 'Tutores socráticos';
$string['noconversations'] = 'Nenhuma conversa foi iniciada ainda.';
$string['objective'] = 'Objetivo de aprendizagem';
$string['objective_help'] = 'Descreva o que o estudante deve compreender, justificar, comparar ou aplicar.';
$string['pendingresponse'] = 'A mensagem anterior do estudante ainda está aguardando resposta da IA. Tente novamente esse turno antes de iniciar outro.';
$string['placeholder'] = 'Explique seu raciocínio, faça uma pergunta ou responda ao tutor…';
$string['pluginadministration'] = 'Administração do tutor socrático';
$string['pluginname'] = 'Tutor socrático';
$string['privacy:metadata:aibridge'] = 'Conteúdo da conversa enviado ao local_ai_bridge';
$string['privacy:metadata:aibridge:contentbase'] = 'Conteúdo-base definido pelo professor e texto de arquivos de apoio processáveis.';
$string['privacy:metadata:aibridge:messages'] = 'Histórico recente mínimo da conversa necessário para gerar a próxima resposta do tutor.';
$string['privacy:metadata:conversations'] = 'Armazena cada conversa do estudante e seu estado de conclusão.';
$string['privacy:metadata:conversations:completedreason'] = 'Motivo pelo qual a conversa foi concluída.';
$string['privacy:metadata:conversations:interactioncount'] = 'Número de interações do estudante.';
$string['privacy:metadata:conversations:socraticid'] = 'Identificador da atividade socrática.';
$string['privacy:metadata:conversations:status'] = 'Estado da conversa.';
$string['privacy:metadata:conversations:teachercompletedat'] = 'Momento em que o professor marcou a conversa como concluída.';
$string['privacy:metadata:conversations:teachercompletedby'] = 'Identificador do professor que marcou a conversa como concluída.';
$string['privacy:metadata:conversations:timecreated'] = 'Momento de criação da conversa.';
$string['privacy:metadata:conversations:timemodified'] = 'Última modificação da conversa.';
$string['privacy:metadata:conversations:userid'] = 'Identificador do estudante.';
$string['privacy:metadata:messages'] = 'Armazena mensagens do estudante e da IA necessárias para manter a conversa socrática.';
$string['privacy:metadata:messages:clientid'] = 'Identificador gerado no cliente para impedir envios duplicados.';
$string['privacy:metadata:messages:content'] = 'Conteúdo da mensagem.';
$string['privacy:metadata:messages:conversationid'] = 'Identificador da conversa.';
$string['privacy:metadata:messages:messagetype'] = 'Tipo da mensagem, como mensagem comum, dica ou pedido de resposta final.';
$string['privacy:metadata:messages:replytoid'] = 'Identificador da mensagem do estudante à qual uma mensagem do assistente responde.';
$string['privacy:metadata:messages:role'] = 'Papel da mensagem, como estudante ou assistente.';
$string['privacy:metadata:messages:timecreated'] = 'Momento de criação da mensagem.';
$string['privacy:metadata:messages:userid'] = 'Identificador do usuário associado à mensagem.';
$string['ratelimited'] = 'Muitas mensagens foram enviadas em pouco tempo. Aguarde antes de enviar outra.';
$string['retry'] = 'Tentar novamente a resposta da IA';
$string['retryableerror'] = 'Sua mensagem foi salva, mas a resposta da IA não pôde ser gerada. Você pode tentar novamente sem duplicar a mensagem.';
$string['send'] = 'Enviar';
$string['settings:historymessages'] = 'Mensagens de histórico enviadas à IA';
$string['settings:historymessages_desc'] = 'Quantidade máxima de mensagens recentes da conversa incluídas em cada requisição de IA.';
$string['settings:maxcontextchars'] = 'Máximo de caracteres da base';
$string['settings:maxcontextchars_desc'] = 'Quantidade máxima combinada de caracteres do conteúdo da atividade e de arquivos de apoio processáveis enviada em uma requisição.';
$string['settings:ratelimitperminute'] = 'Mensagens por minuto';
$string['settings:ratelimitperminute_desc'] = 'Limite local por usuário aplicado antes da chamada ao local_ai_bridge. Créditos do bridge e limites do provider continuam independentes.';
$string['socratic:addinstance'] = 'Adicionar uma atividade de tutor socrático';
$string['socratic:view'] = 'Visualizar e usar o tutor socrático';
$string['socratic:viewallconversations'] = 'Visualizar todas as conversas dos estudantes';
$string['status'] = 'Status';
$string['statusactive'] = 'Ativa';
$string['statuscompleted'] = 'Concluída';
$string['supportfiles'] = 'Arquivos de apoio';
$string['supportfiles_help'] = 'Somente formatos textuais processados por esta versão são aceitos: TXT, Markdown, HTML, CSV, JSON e XML. Cada arquivo é limitado a 1 MB e pode entrar no contexto fundamentado da IA.';
$string['supportmaterials'] = 'Materiais de apoio';
$string['teachercompleted'] = 'Marcada como concluída pelo professor';
$string['tutorbehavior'] = 'Comportamento do tutor';
$string['tutorbehavior_help'] = 'Orientações opcionais sobre tom, estilo das perguntas, profundidade e abordagem pedagógica. Essas instruções não podem sobrescrever as regras de fundamentação e segurança.';
$string['unsupportedfilecontext'] = 'Este arquivo fica disponível ao estudante, mas não é interpretado no contexto da IA nesta versão.';
$string['viewconversation'] = 'Visualizar conversa';
