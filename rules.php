<?php
/**
 * MonoTalk - Полные правила сообщества (RU/EN)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$isEn = $lang === 'en';

$pageTitle = $isEn ? 'Community rules' : 'Правила сообщества';
$discussionLabel = $isEn ? 'Discussion' : 'Обсуждение';

$rulesByLang = [
    'ru' => [
        'intro' => 'Здесь собраны все правила нашего сообщества. Соблюдайте их, и всем будет комфортно общаться.',
        'footer' => 'Вопросы по правилам? Напишите в разделе ',
        'sections' => [
            'strict' => [
                'title' => '🚫 Строгие правила',
                'desc' => 'Нарушения влекут немедленные последствия вплоть до постоянного бана.',
            ],
            'important' => [
                'title' => '⚠️ Важные правила',
                'desc' => 'Регулярные нарушения приводят к предупреждениям, временным или постоянным ограничениям.',
            ],
            'recommended' => [
                'title' => '👍 Рекомендации',
                'desc' => 'Следование этим правилам делает форум приятнее для всех.',
            ],
            'soft' => [
                'title' => '💡 Дополнительные советы',
                'desc' => 'Небольшие подсказки для комфортного общения.',
            ],
        ],
        'rules' => [
            'strict' => [
                ['title' => 'Запрещены угрозы и призывы к насилию', 'desc' => 'Любые угрозы в адрес пользователей, призывы к насилию или экстремизму влекут немедленный бан без предупреждения.'],
                ['title' => 'Никакой детской порнографии и NSFW с несовершеннолетними', 'desc' => 'Строго запрещён любой контент, связанный с несовершеннолетними в неприемлемом контексте. Нулевая толерантность.'],
                ['title' => 'Запрещена массовая рассылка и накрутка', 'desc' => 'Боты, спам-рассылки, искусственная накрутка лайков и подписчиков — бан аккаунта.'],
                ['title' => 'Не раскрывайте личные данные других людей', 'desc' => 'Доксинг, публикация адресов, телефонов, фото без согласия — серьёзное нарушение с последствиями.'],
                ['title' => 'Мошенничество и фишинг', 'desc' => 'Обман пользователей, сбор данных под видом официальных сервисов, скам — немедленный бан.'],
            ],
            'important' => [
                ['title' => 'Уважайте других участников', 'desc' => 'Оскорбления, травля, дискриминация по расе, полу, ориентации не допускаются. Спорьте по существу, не переходя на личности.'],
                ['title' => 'Не спамьте', 'desc' => 'Один и тот же пост или реклама в разные разделы — не делайте так. Дайте людям дышать.'],
                ['title' => 'Пишите по теме раздела', 'desc' => 'Мемы — в Мемы, код — в Программирование. Неправильная категория = пост могут перенести или скрыть.'],
                ['title' => 'Не дублируйте чужие посты', 'desc' => 'Если кто-то уже поднял тему — присоединяйтесь к обсуждению, а не создавайте копию.'],
                ['title' => 'Не накручивайте себя', 'desc' => 'Альтернативные аккаунты для лайков своих постов, массовые фейковые комментарии — это заметят.'],
            ],
            'recommended' => [
                ['title' => 'Используйте осмысленные заголовки', 'desc' => '«Помогите» или «Вопрос» — не очень информативно. Опишите суть, чтобы другим было проще понять.'],
                ['title' => 'Проверьте, не задавали ли такой вопрос раньше', 'desc' => 'Поиск по форуму — ваш друг. Возможно, ответ уже есть.'],
                ['title' => 'Не пишите ЗАГЛАВНЫМИ БУКВАМИ', 'desc' => 'Это воспринимается как крик. Для выделения лучше использовать **жирный** или *курсив*, если поддерживается.'],
                ['title' => 'Ссылки — в меру', 'desc' => 'Один-два релевантных — ок. Десяток на каждый пост — уже спам.'],
                ['title' => 'Будьте адекватны в спорах', 'desc' => 'Разное мнение — норма. Переход на личности и токсичность — нет.'],
                ['title' => 'Помогайте новичкам', 'desc' => 'Мы все когда-то начинали. Вежливый ответ лучше, чем «погугли».'],
                ['title' => 'Мемы — в соответствующем разделе', 'desc' => 'Смешно — отлично, но не засоряйте серьёзные обсуждения шутками не в тему.'],
                ['title' => 'Источники новостей', 'desc' => 'При публикации новостей указывайте источник. Фейковые новости — не приветствуются.'],
                ['title' => 'Конструктивная критика', 'desc' => 'Критикуйте идеи, а не людей. «Код можно улучшить так» лучше, чем «ты плохо пишешь».'],
                ['title' => 'Создавайте обсуждения, а не скандалы', 'desc' => 'Спорные темы — да. Переход на личности и эскалация — нет.'],
            ],
            'soft' => [
                ['title' => 'Не засоряйте ленту однотипными постами', 'desc' => 'Десять постов подряд от одного человека за минуту — излишне. Объедините в один.'],
                ['title' => 'Теги и форматирование', 'desc' => 'Пользуйтесь тегами и структурой — так удобнее читать длинные посты.'],
                ['title' => 'Редактируйте опечатки', 'desc' => 'Особенно в заголовках. Это занимает секунду и выглядит аккуратнее.'],
                ['title' => 'Реагируйте на обратную связь', 'desc' => 'Если модератор попросил что-то исправить — лучше сделать.'],
                ['title' => 'Один аккаунт — один человек', 'desc' => 'Общие аккаунты сбивают с толку. Создайте свой — это бесплатно.'],
                ['title' => 'Не флудите в чужих темах', 'desc' => 'Оффтоп в обсуждении — лишний. Создайте свой пост, если тема другая.'],
                ['title' => 'Соблюдайте этикет при цитировании', 'desc' => 'Длинные цитаты — сокращайте. Оставляйте только релевантное.'],
                ['title' => 'Не злоупотребляйте анонимностью', 'desc' => 'Анонимность — для чувствительных тем, а не для травли и сброса ответственности.'],
            ],
        ],
    ],
    'en' => [
        'intro' => 'All community rules are collected here. Follow them and everyone will enjoy a comfortable discussion.',
        'footer' => 'Questions about the rules? Post in ',
        'sections' => [
            'strict' => [
                'title' => '🚫 Strict rules',
                'desc' => 'Violations lead to immediate consequences, up to a permanent ban.',
            ],
            'important' => [
                'title' => '⚠️ Important rules',
                'desc' => 'Repeated violations may result in warnings and temporary or permanent restrictions.',
            ],
            'recommended' => [
                'title' => '👍 Recommendations',
                'desc' => 'Following these rules keeps the forum better for everyone.',
            ],
            'soft' => [
                'title' => '💡 Additional tips',
                'desc' => 'Small hints for smoother, more pleasant communication.',
            ],
        ],
        'rules' => [
            'strict' => [
                ['title' => 'Threats and calls for violence are prohibited', 'desc' => 'Any threats to users or calls for violence/extremism result in an immediate ban without warnings.'],
                ['title' => 'No sexual content involving minors / NSFW with underage people', 'desc' => 'Any content connected to minors in an inappropriate context is strictly forbidden. Zero tolerance.'],
                ['title' => 'No mass spam or engagement boosting', 'desc' => 'Bots, spam campaigns, artificial likes/subscribers boosting — account ban.'],
                ['title' => 'Do not disclose personal data of others', 'desc' => 'Doxxing, publishing addresses/phone numbers/photos without consent is a serious violation with consequences.'],
                ['title' => 'Fraud and phishing', 'desc' => 'Deception, collecting data under the guise of official services, scams — immediate ban.'],
            ],
            'important' => [
                ['title' => 'Respect other participants', 'desc' => 'Insults, harassment, and discrimination based on race, gender, or orientation are not allowed. Debate ideas, not people.'],
                ['title' => 'Don’t spam', 'desc' => 'Don’t post the same content or ads in multiple sections. Let people breathe.'],
                ['title' => 'Stay on topic for the selected category', 'desc' => 'Memes belong in Memes, code belongs in Programming. Wrong category may result in moving or hiding your post.'],
                ['title' => 'Don’t duplicate someone else’s posts', 'desc' => 'If a topic already exists, join the discussion instead of reposting the same thing.'],
                ['title' => 'Don’t game the system', 'desc' => 'Using alternative accounts to like your own posts or posting massive fake comments will be detected.'],
            ],
            'recommended' => [
                ['title' => 'Use meaningful titles', 'desc' => '“Help” or “Question” is not very informative. Describe the essence so others can understand quickly.'],
                ['title' => 'Check if the question was asked before', 'desc' => 'Use the forum search — answers may already exist.'],
                ['title' => 'Avoid writing in ALL CAPS', 'desc' => 'It’s perceived as shouting. For emphasis use bold or italics if supported.'],
                ['title' => 'Links are okay, but in moderation', 'desc' => 'One or two relevant links are fine. Too many per post looks like spam.'],
                ['title' => 'Be reasonable in disputes', 'desc' => 'Different opinions are normal. Personal attacks and toxicity are not.'],
                ['title' => 'Help newcomers', 'desc' => 'We all started somewhere. A polite answer is better than “go google it”.'],
                ['title' => 'Keep memes in the right section', 'desc' => 'Funny is great, but don’t flood serious discussions with off-topic jokes.'],
                ['title' => 'News sources matter', 'desc' => 'When posting news, include the source. Fake news isn’t welcome.'],
                ['title' => 'Constructive criticism', 'desc' => 'Criticize ideas, not people. “The code can be improved like this” is better than “you write badly”.'],
                ['title' => 'Create discussions, not drama', 'desc' => 'Controversial topics are okay. Escalation and personal attacks are not.'],
            ],
            'soft' => [
                ['title' => 'Don’t clutter the feed with repetitive posts', 'desc' => 'Ten posts in a minute by the same person is too much. Combine them into one.'],
                ['title' => 'Tags and formatting help', 'desc' => 'Use structure and tags — it’s easier to read long posts.'],
                ['title' => 'Fix typos', 'desc' => 'Especially in titles. It takes a second and makes things look cleaner.'],
                ['title' => 'React to feedback', 'desc' => 'If a moderator asks you to fix something — it’s better to do it.'],
                ['title' => 'One account — one person', 'desc' => 'Shared accounts confuse everyone. Create your own — it’s free.'],
                ['title' => 'Don’t flood someone else’s thread', 'desc' => 'Off-topic comments are extra. Create your own post if the topic is different.'],
                ['title' => 'Be polite when quoting', 'desc' => 'Long quotes should be shortened. Keep only what’s relevant.'],
                ['title' => 'Don’t abuse anonymity', 'desc' => 'Anonymity is for sensitive topics, not for harassment or escaping responsibility.'],
            ],
        ],
    ],
];

$rulesPack = $rulesByLang[$lang] ?? $rulesByLang['ru'];
$rules = $rulesPack['rules'];
$sections = $rulesPack['sections'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container rules-page">
    <div class="rules-content">
        <h1><?= $isEn ? 'Rules for r/MonoTalk' : 'Правила r/MonoTalk' ?></h1>
        <p class="rules-intro"><?= e($rulesPack['intro']) ?></p>

        <section class="rules-section rules-strict">
            <h2><?= e($sections['strict']['title']) ?></h2>
            <p class="section-desc"><?= e($sections['strict']['desc']) ?></p>
            <ol class="rules-full-list">
                <?php foreach ($rules['strict'] as $r): ?>
                <li>
                    <strong><?= e($r['title']) ?></strong>
                    <p><?= e($r['desc']) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="rules-section rules-important">
            <h2><?= e($sections['important']['title']) ?></h2>
            <p class="section-desc"><?= e($sections['important']['desc']) ?></p>
            <ol class="rules-full-list" start="<?= count($rules['strict']) + 1 ?>">
                <?php foreach ($rules['important'] as $r): ?>
                <li>
                    <strong><?= e($r['title']) ?></strong>
                    <p><?= e($r['desc']) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="rules-section rules-recommended">
            <h2><?= e($sections['recommended']['title']) ?></h2>
            <p class="section-desc"><?= e($sections['recommended']['desc']) ?></p>
            <ol class="rules-full-list" start="<?= count($rules['strict']) + count($rules['important']) + 1 ?>">
                <?php foreach ($rules['recommended'] as $r): ?>
                <li>
                    <strong><?= e($r['title']) ?></strong>
                    <p><?= e($r['desc']) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <section class="rules-section rules-soft">
            <h2><?= e($sections['soft']['title']) ?></h2>
            <p class="section-desc"><?= e($sections['soft']['desc']) ?></p>
            <ol class="rules-full-list" start="<?= count($rules['strict']) + count($rules['important']) + count($rules['recommended']) + 1 ?>">
                <?php foreach ($rules['soft'] as $r): ?>
                <li>
                    <strong><?= e($r['title']) ?></strong>
                    <p><?= e($r['desc']) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>
        </section>

        <p class="rules-footer">
            <?= e($rulesPack['footer']) ?>
            <a href="<?= e(BASE_URL) ?>index.php?category=discussion"><?= e($discussionLabel) ?></a>.
        </p>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
