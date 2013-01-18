ALTER TABLE "as".as_languages  RENAME TO as_locale_languages;


ALTER TABLE "as"."as_locale_languages"  DROP CONSTRAINT "as_languages_pkey" CASCADE;



--$ scan_tables locale t
--$ scan_tables lang t
--$ scan_tables user t
--$ scan_tables template t

INSERT INTO as_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (1, 'as');
UPDATE as_locale_languages SET lang_key = lang_key || '_1';

--Pas de langue "Espagnol" (/!\: locale_domain_id==1 -> AS)
INSERT INTO as_locale_languages (lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id) values('es-es_1', 'esp', 'spa', 'en-us_1','Spanish',1)

--Pas de projet "Distrib" (/!\: parent_id==4 -> Web)
INSERT INTO as_projects_list (project_order, project_name, parent_id) values(NULL,'Distrib','4')

UPDATE "as".as_locale_languages SET locale_domain_id = 1;

-- Auto fallback
INSERT INTO public.prod_locale_languages
(lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id)
SELECT replace(lang_key,'_1',''), country_code, lang_code, lang_key, lang_name, null
FROM public.prod_locale_languages WHERE locale_domain_id = 1

--$ scan_tables locale t
--$ v v v v v v 