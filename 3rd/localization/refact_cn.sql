---------------------------------------
------- Refactoring module Lang -------

-- drop table ivs_locale_languages;
ALTER TABLE public.prod_languages  RENAME TO prod_locale_languages;
ALTER TABLE "public"."prod_locale_languages"  DROP CONSTRAINT "prod_languages_pkey" CASCADE;


--$ scan_tables locale t
--$ scan_tables lang t
--$ scan_tables user t


UPDATE public.prod_locale_languages SET lang_key = lang_key || '_1';

--UPDATE ivs_languages_last_update SET lang_key = lang_key || '_1'

INSERT INTO public.prod_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (1, 'IVS');

UPDATE public.prod_locale_languages SET locale_domain_id = 1;

INSERT INTO public.prod_locale_languages
(lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id)
SELECT replace(lang_key,'_1',''), country_code, lang_code, lang_key, lang_name, null
FROM public.prod_locale_languages WHERE locale_domain_id = 1
--$ scan_tables locale t
--$ v v v v v v