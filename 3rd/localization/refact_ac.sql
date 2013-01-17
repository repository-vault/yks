---------------------------------------
------- Refactoring module Lang -------

-- cleanup
DROP TABLE ivs_myks_titles;

-- drop table ivs_locale_languages;
ALTER TABLE public.ivs_languages  RENAME TO ivs_locale_languages;
ALTER TABLE "public"."ivs_locale_languages"  DROP CONSTRAINT "ivs_languages_pkey" CASCADE;



--$ scan_tables locale t
--$ scan_tables lang t
--$ scan_tables user t
--$ scan_tables optician t

UPDATE ivs_locale_languages SET lang_key = lang_key || '_1';

--UPDATE ivs_languages_last_update SET lang_key = lang_key || '_1'

INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (1, 'IVS');
--- Le reste est pour Activisu
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (2, 'Visioffice');
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (3, 'Expert');
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (4, 'Eyestation');
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (5, 'Swing');
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (6, 'Visioffice2');
INSERT INTO ivs_locale_domains_list (locale_domain_id, locale_domain_name) VALUES (7, 'Expert4');

UPDATE ivs_locale_languages SET locale_domain_id = 1;

--$ scan_tables locale t
--$ v v v v v v 


-- A faire pour les 7 domaines !
INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_2'),
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  2
FROM ivs_locale_languages
WHERE locale_domain_id = 1

INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_3'),
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  3   
FROM ivs_locale_languages
WHERE locale_domain_id = 1

INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_4'), -- CHANGE ME !
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  4 
FROM ivs_locale_languages
WHERE locale_domain_id = 1

INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_5'),
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  5
FROM ivs_locale_languages
WHERE locale_domain_id = 1

INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_6'),
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  6
FROM ivs_locale_languages
WHERE locale_domain_id = 1

INSERT INTO public.ivs_locale_languages
( lang_key, country_code, lang_code, lang_fallback, lang_name, locale_domain_id ) 
SELECT
  replace(lang_key,'_1','_7'),
  country_code,
  lang_code,
  lang_key AS lang_fallback,
  lang_name,
  7
FROM ivs_locale_languages
WHERE locale_domain_id = 1


-- On copie les trads dans les nouvelles langues.
  -- Visioffice
INSERT INTO public.ivs_locale_values
  ( item_key, lang_key, "value") 
SELECT 
  item_key,
  replace(lang_key,'_1','_2'),
  value
FROM public.ivs_locale_values
WHERE item_key like 'VISIOFFICE_%'
AND lang_key ilike '%_1'

  -- EyeStation
INSERT INTO public.ivs_locale_values
  ( item_key, lang_key, "value") 
SELECT 
  item_key,
  replace(lang_key,'_1','_4'),
  value
FROM public.ivs_locale_values
WHERE item_key like 'EYESTATION_%'
AND lang_key ilike '%_1'

  -- Expert
INSERT INTO public.ivs_locale_values
  ( item_key, lang_key, "value") 
SELECT 
  item_key,
  replace(lang_key,'_1','_3'),
  value
FROM public.ivs_locale_values
WHERE item_key NOT LIKE 'EYESTATION_%'
AND   item_key NOT LIKE 'VISIOFFICE_%'
AND lang_key ilike '%_1'

  -- Swing
INSERT INTO public.ivs_locale_values
  ( item_key, lang_key, "value") 
SELECT 
  item_key,
  replace(lang_key,'_1','_5'),
  value
FROM public.ivs_locale_values
WHERE item_key NOT LIKE 'EYESTATION_%'
AND   item_key NOT LIKE 'VISIOFFICE_%'
AND lang_key ilike '%_1'



--- On réatribue les langues / domaines aux distrib
-- Essilor 
UPDATE ivs_users_profile_locale_languages
SET lang_key = replace(lang_key,'_1','_2')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3435, 1) -- Essilor 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3477, 1) -- Essilor interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3520, 1) -- Essilor externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3435 UNION SELECT 3477 UNION SELECT 3520 
)

-- Eyestation (BBGR)
UPDATE ivs_users_profile_locale_languages
SET lang_key = replace(lang_key,'_1','_4')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3074, 1) -- BBGR 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3476, 1) -- BBGR interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3519, 1) -- BBGR externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3074 UNION SELECT 3476 UNION SELECT 3519 
)



-- Expert
UPDATE ivs_users_profile_locale_languages
SET lang_key = replace(lang_key,'_1','_3')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3170, 1) -- Réseaux IVS 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3440, 1) -- Expert interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3518, 1) -- Expert externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3617, 1) -- IVS - Export
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3898, 1) -- Essilor (IVS)
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3899, 1) -- IVS International
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4196, 1) -- Activisu France Network
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4221, 1) -- Essilor (IVS) Swing 3+
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT 3170 UNION SELECT 3440 UNION SELECT 3518 UNION SELECT 3617
  UNION SELECT 3898 UNION SELECT 3899 UNION SELECT 4196 UNION SELECT 4221
)




-- On réatribue les langues des pays (pour les packs)

UPDATE ivs_users_profile SET user_lang = user_lang || '_1' WHERE user_lang NOT LIKE '%_%'

-- Essilor 
UPDATE ivs_users_profile
SET user_lang = replace(user_lang,'_1','_2')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3435, 1) -- Essilor 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3477, 1) -- Essilor interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3520, 1) -- Essilor externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3435 UNION SELECT 3477 UNION SELECT 3520 
)

-- Eyestation (BBGR)
UPDATE ivs_users_profile
SET user_lang = replace(user_lang,'_1','_4')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3074, 1) -- BBGR 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3476, 1) -- BBGR interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3519, 1) -- BBGR externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3074 UNION SELECT 3476 UNION SELECT 3519 
)



-- Expert
UPDATE ivs_users_profile
SET user_lang = replace(user_lang,'_1','_3')
WHERE user_id IN (
  SELECT user_id FROM ivs_users_tree(3170, 1) -- Réseaux IVS 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3440, 1) -- Expert interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3518, 1) -- Expert externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3617, 1) -- IVS - Export
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3898, 1) -- Essilor (IVS)
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3899, 1) -- IVS International
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4196, 1) -- Activisu France Network
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4221, 1) -- Essilor (IVS) Swing 3+
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT 3170 UNION SELECT 3440 UNION SELECT 3518 UNION SELECT 3617
  UNION SELECT 3898 UNION SELECT 3899 UNION SELECT 4196 UNION SELECT 4221
)


---- On donne les domaines aux personnes concernées.
-- Essilor 
INSERT INTO ivs_users_profile_locale_domains
(user_id, locale_domain_id)
SELECT user_id, 2
FROM ivs_users_profile_locale_projects pp
WHERE true
AND pp.project_id IS NOT NULL
AND user_id IN (
  SELECT user_id FROM ivs_users_tree(3435, 1) -- Essilor 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3477, 1) -- Essilor interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3520, 1) -- Essilor externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3435 UNION SELECT 3477 UNION SELECT 3520 
)
GROUP BY user_id

-- Eyestation (BBGR)
INSERT INTO ivs_users_profile_locale_domains
(user_id, locale_domain_id)
SELECT user_id, 4
FROM ivs_users_profile_locale_projects pp
WHERE true
AND pp.project_id IS NOT NULL
AND  user_id IN (
  SELECT user_id FROM ivs_users_tree(3074, 1) -- BBGR 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3476, 1) -- BBGR interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3519, 1) -- BBGR externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)    
  UNION SELECT 3074 UNION SELECT 3476 UNION SELECT 3519 
)
GROUP BY user_id



-- Expert
INSERT INTO ivs_users_profile_locale_domains
(user_id, locale_domain_id)
SELECT user_id, 3
FROM ivs_users_profile_locale_projects pp
WHERE true
AND pp.project_id IS NOT NULL
AND  user_id IN (
  SELECT user_id FROM ivs_users_tree(3170, 1) -- Réseaux IVS 
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3440, 1) -- Expert interne
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3518, 1) -- Expert externe
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3617, 1) -- IVS - Export
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3898, 1) -- Essilor (IVS)
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(3899, 1) -- IVS International
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4196, 1) -- Activisu France Network
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT user_id FROM ivs_users_tree(4221, 1) -- Essilor (IVS) Swing 3+
    AS func(user_id INTEGER, parent_id INTEGER, depth INTEGER)
  UNION SELECT 3170 UNION SELECT 3440 UNION SELECT 3518 UNION SELECT 3617
  UNION SELECT 3898 UNION SELECT 3899 UNION SELECT 4196 UNION SELECT 4221
)
GROUP BY user_id




--- --- --- END --- --- ---