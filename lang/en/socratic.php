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
 * English strings for mod_socratic.
 *
 * @package   mod_socratic
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Socratic tutor';
$string['modulename'] = 'Socratic tutor';
$string['modulenameplural'] = 'Socratic tutors';
$string['pluginadministration'] = 'Socratic tutor administration';
$string['socratic:addinstance'] = 'Add a new Socratic tutor activity';
$string['socratic:view'] = 'View and use the Socratic tutor';
$string['socratic:viewallconversations'] = 'View all learner conversations';
$string['objective'] = 'Learning objective';
$string['objective_help'] = 'Describe what the learner should understand, justify, compare or apply.';
$string['contentbase'] = 'Content / knowledge base';
$string['contentbase_help'] = 'The tutor must remain grounded in this content. It should contain the authoritative textual material for the activity.';
$string['supportfiles'] = 'Supporting files';
$string['supportfiles_help'] = 'Only text formats processed by this version are accepted: TXT, Markdown, HTML, CSV, JSON and XML. Files are limited to 1 MB each and may be included in the grounded AI context.';
$string['tutorbehavior'] = 'Tutor behaviour';
$string['tutorbehavior_help'] = 'Optional teacher guidance about tone, question style, depth and pedagogical approach. It cannot override grounding or safety rules.';
$string['maxinteractions'] = 'Maximum interactions';
$string['maxinteractions_help'] = 'Maximum learner turns in this activity. Hints and final-answer requests also count as interactions.';
$string['allowhint'] = 'Allow hint requests';
$string['allowfinalanswer'] = 'Allow final-answer requests';
$string['completioncriterion'] = 'Completion criterion';
$string['completioncriterion:interactions'] = 'After N interactions';
$string['completioncriterion:ended'] = 'After the conversation is ended';
$string['completioncriterion:teacher'] = 'After a teacher marks the conversation complete';
$string['completioncriterion:manual'] = 'Manual Moodle completion';
$string['completioninteractions'] = 'Interactions required for completion';
$string['completiondetail:interactions'] = 'Make at least {$a} interactions with the Socratic tutor';
$string['completiondetail:ended'] = 'Complete the Socratic conversation';
$string['completiondetail:teacher'] = 'Have the conversation marked complete by a teacher';
$string['assistancelabel'] = 'AI learning assistant';
$string['send'] = 'Send';
$string['hint'] = 'Ask for a hint';
$string['finalanswer'] = 'Request final answer';
$string['endconversation'] = 'End conversation';
$string['conversationended'] = 'This conversation has ended.';
$string['interactioncount'] = '{$a->used} of {$a->max} interactions used';
$string['placeholder'] = 'Explain your reasoning, ask a question, or respond to the tutor…';
$string['retry'] = 'Retry AI response';
$string['retryableerror'] = 'Your message was saved, but the AI response could not be generated. You can retry without sending the message twice.';
$string['pendingresponse'] = 'The previous learner message is still waiting for an AI response. Retry that turn before starting another.';
$string['ratelimited'] = 'Too many messages were sent in a short period. Please wait before sending another one.';
$string['lockunavailable'] = 'The conversation is busy processing another request. Please try again.';
$string['maxinteractionsreached'] = 'The maximum number of interactions for this activity has been reached.';
$string['invalidaction'] = 'This action is not allowed for this activity.';
$string['aiunavailable'] = 'The AI tutor is temporarily unavailable. Your message was saved and can be retried.';
$string['empty_message'] = 'Enter a message before sending.';
$string['conversations'] = 'Learner conversations';
$string['conversation'] = 'Conversation';
$string['learner'] = 'Learner';
$string['status'] = 'Status';
$string['statusactive'] = 'Active';
$string['statuscompleted'] = 'Completed';
$string['lastupdated'] = 'Last updated';
$string['interactions'] = 'Interactions';
$string['viewconversation'] = 'View conversation';
$string['markcomplete'] = 'Mark conversation complete';
$string['teachercompleted'] = 'Marked complete by teacher';
$string['noconversations'] = 'No conversations have started yet.';
$string['supportmaterials'] = 'Supporting materials';
$string['unsupportedfilecontext'] = 'This file is available to the learner but is not parsed into the AI context in this version.';
$string['settings:ratelimitperminute'] = 'Messages per minute';
$string['settings:ratelimitperminute_desc'] = 'Local per-user rate limit applied before calling local_ai_bridge. Bridge credits and provider limits still apply independently.';
$string['settings:historymessages'] = 'History messages sent to AI';
$string['settings:historymessages_desc'] = 'Maximum recent conversation messages included in each AI request.';
$string['settings:maxcontextchars'] = 'Maximum knowledge-base characters';
$string['settings:maxcontextchars_desc'] = 'Maximum combined number of characters from activity text and processable supporting files sent in one AI request.';
$string['privacy:metadata:conversations'] = 'Stores each learner conversation and its completion state.';
$string['privacy:metadata:conversations:socraticid'] = 'The Socratic activity identifier.';
$string['privacy:metadata:conversations:userid'] = 'The learner user identifier.';
$string['privacy:metadata:conversations:status'] = 'Conversation status.';
$string['privacy:metadata:conversations:interactioncount'] = 'Number of learner interactions.';
$string['privacy:metadata:conversations:completedreason'] = 'Reason the conversation was completed.';
$string['privacy:metadata:conversations:teachercompletedby'] = 'User identifier of the teacher who marked the conversation complete.';
$string['privacy:metadata:conversations:teachercompletedat'] = 'Time the teacher marked the conversation complete.';
$string['privacy:metadata:conversations:timecreated'] = 'Conversation creation time.';
$string['privacy:metadata:conversations:timemodified'] = 'Conversation last modification time.';
$string['privacy:metadata:messages'] = 'Stores learner and AI messages required to maintain the Socratic conversation.';
$string['privacy:metadata:messages:conversationid'] = 'Conversation identifier.';
$string['privacy:metadata:messages:userid'] = 'User identifier associated with the message.';
$string['privacy:metadata:messages:role'] = 'Message role, such as learner or assistant.';
$string['privacy:metadata:messages:messagetype'] = 'Message type, such as ordinary message, hint or final-answer request.';
$string['privacy:metadata:messages:content'] = 'Message content.';
$string['privacy:metadata:messages:clientid'] = 'Client-generated identifier used to prevent duplicate submissions.';
$string['privacy:metadata:messages:replytoid'] = 'Identifier of the learner message to which an assistant message replies.';
$string['privacy:metadata:messages:timecreated'] = 'Message creation time.';
$string['privacy:metadata:aibridge'] = 'Conversation content sent to local_ai_bridge';
$string['privacy:metadata:aibridge:messages'] = 'The minimum recent conversation history required to generate the next tutor response.';
$string['privacy:metadata:aibridge:contentbase'] = 'Teacher-provided grounding content and processable support-file text.';
$string['eventconversationstarted'] = 'Socratic conversation started';
$string['eventmessagesent'] = 'Socratic message sent';
$string['eventconversationcompleted'] = 'Socratic conversation completed';
$string['messageyou'] = 'You';
$string['messagetutor'] = 'AI learning assistant';
$string['backtoactivity'] = 'Back to activity';
$string['completedbylimit'] = 'Conversation ended because the interaction limit was reached.';
$string['completedbystudent'] = 'Conversation ended by the learner.';
$string['completedbyteacher'] = 'Conversation marked complete by a teacher.';
$string['completionupdated'] = 'Completion status updated.';
$string['allgroups'] = 'All accessible groups';
