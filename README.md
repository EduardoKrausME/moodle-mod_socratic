# mod_socratic

Socratic AI tutoring activity for Moodle.

The activity guides learners through questions, justification, counterexamples and reflection instead of immediately giving the answer. All AI generation is routed exclusively through `local_ai_bridge` using the purpose `socratic-tutor`.

## Main behaviour

Teachers configure:

- activity name and introduction;
- learning objective;
- authoritative text/content base;
- support files;
- additional tutor behaviour;
- maximum interactions;
- hint availability;
- final-answer availability;
- completion criterion.

The system prompt enforces Socratic behaviour and grounding. The tutor must ask before simply answering, explore the learner's reasoning, avoid humiliation, indicate uncertainty, avoid unsupported facts and avoid invented sources. Teacher-provided material is treated as reference content rather than executable prompt instructions.

## Reference files

This version accepts only support formats it can process deterministically as UTF-8 text: TXT, Markdown, HTML, CSV, JSON and XML. Each file is limited to 1 MB, and the combined grounding context is still capped by the plugin setting before it is sent to the bridge. Binary formats such as PDF and DOCX are intentionally rejected in this the plugin rather than being stored with a misleading implication that their contents were understood.

## Conversation persistence and retries

Conversation history is stored by `mod_socratic` because it is required for the activity itself. Each browser submission receives a `clientid` idempotency token and the server uses a per-conversation Moodle lock. A duplicate or concurrent request therefore does not create a second learner turn.

The learner message is persisted before `local_ai_bridge` is called. If the bridge or provider fails, the learner does not lose the message, and the same turn can be retried without duplicating it. The pending turn is reconstructed after a page reload, while the server blocks a different new turn until the unanswered one has been resolved or the conversation is ended.

Only a bounded recent history is sent to the bridge. Site administrators can configure the history size and maximum grounding-context size under the activity plugin settings.

## Local rate limiting

The module applies a basic per-user/per-conversation rate limit before invoking the bridge. This is separate from bridge credits and provider-side limits.

## Completion

Available completion modes:

- after N interactions;
- when the conversation is ended;
- after a teacher marks the conversation complete;
- Moodle manual completion.

No automatic grade is generated from subjective AI judgement in the plugin.

## Capabilities

- `mod/socratic:addinstance`
- `mod/socratic:view`
- `mod/socratic:viewallconversations`

Only users with `mod/socratic:viewallconversations` can inspect other users' conversation histories. Separate groups are respected in teacher conversation access.

## Privacy

The Privacy API includes:

- metadata;
- context discovery;
- user discovery in context;
- export;
- `delete_data_for_user`;
- `delete_data_for_all_users_in_context`;
- `delete_data_for_users`.

It covers both learner conversation ownership and the teacher identifier recorded when teacher-based completion is used. The provider also declares that bounded conversation content and teacher-provided grounding material are passed to `local_ai_bridge`, which may route requests to a configured external provider.

## Events

- `conversation_started`
- `message_sent`
- `conversation_completed`

## UI

The learner interface uses Mustache and AMD. It is intentionally presented as an "AI learning assistant" rather than a generic chatbot. The composer uses asynchronous Moodle external functions, disables controls during a request and supports retry after a recoverable AI failure.

## Backup and restore

Activity configuration, support files and, when user data is included, conversations/messages are supported by Moodle backup and restore.
