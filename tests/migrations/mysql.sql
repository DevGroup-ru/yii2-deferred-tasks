SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- База данных: `dotplantru`
--

-- --------------------------------------------------------

--
-- Структура таблицы `deferred_group`
--

DROP TABLE IF EXISTS `deferred_group`;
CREATE TABLE IF NOT EXISTS `deferred_group` (
`id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `allow_parallel_run` tinyint(1) NOT NULL DEFAULT '0',
  `run_last_command_only` tinyint(1) NOT NULL DEFAULT '0',
  `notify_initiator` tinyint(1) NOT NULL DEFAULT '1',
  `notify_roles` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_notification` tinyint(1) NOT NULL DEFAULT '1',
  `group_notifications` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `deferred_queue`
--

DROP TABLE IF EXISTS `deferred_queue`;
CREATE TABLE IF NOT EXISTS `deferred_queue` (
`id` int(11) NOT NULL,
  `deferred_group_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `initiated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_repeating_task` tinyint(1) NOT NULL DEFAULT '0',
  `cron_expression` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `next_start` timestamp NULL DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '0',
  `last_run_date` timestamp NULL DEFAULT NULL,
  `console_route` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cli_command` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `command_arguments` text COLLATE utf8_unicode_ci,
  `notify_initiator` tinyint(1) NOT NULL DEFAULT '1',
  `notify_roles` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email_notification` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `deferred_group`
--
ALTER TABLE `deferred_group`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `deferred_queue`
--
ALTER TABLE `deferred_queue`
 ADD PRIMARY KEY (`id`), ADD KEY `by_status` (`status`,`next_start`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `deferred_group`
--
ALTER TABLE `deferred_group`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT для таблицы `deferred_queue`
--
ALTER TABLE `deferred_queue`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

