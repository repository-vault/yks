-- something like that will be usefull if you want to check auto_increment integrity ( my guess is that you are doing something in the wrong way)

DECLARE
rec RECORD;
increment_table CONSTANT varchar := 'zks_autoincrement';
increment_column_name varchar;
increment_key varchar;

value_max integer;
value_next integer;
value_lastval integer;
BEGIN


SELECT * INTO rec FROM zks_autoincrement WHERE table_name= $1;


if(rec.increment_key notnull AND $2) THEN
	return rec.increment_lastval;
    
ELSEIF (rec.increment_key is null ) THEN

	SELECT 
		regexp_replace(column_default,E'auto_increment\\((.)(.*)\\1.*', E'\\2') as increment_k
		INTO rec
		FROM information_schema.columns
	    WHERE table_name=$1;
    increment_key:=rec.increment_k;


	SELECT  column_name   into rec
			FROM information_schema.constraint_column_usage col
        	INNER JOIN information_schema.table_constraints USING(constraint_name)
	        WHERE col.table_name= $1
    	    	AND constraint_type='PRIMARY KEY'
        	LIMIT 1;
    increment_column_name:=rec.column_name;
    
    EXECUTE('UPDATE '||increment_table||' SET table_name ='''||$1||''',column_name='''||increment_column_name||''' WHERE increment_key='''||increment_key||'''') ;
	EXECUTE('SELECT * FROM '||increment_table||' WHERE increment_key='''||increment_key||'''') INTO rec ;
ELSE 
    increment_key:=rec.increment_key;
    increment_column_name:=rec.column_name;
END IF;

value_next:=COALESCE(rec.increment_value,1);
value_lastval:=COALESCE(rec.increment_lastval,1);


EXECUTE ('SELECT MAX( '||increment_column_name|| ') as increment_value  FROM ' || $1 || ';') INTO rec;
value_max:=COALESCE(rec.increment_value,1);

if(value_next != value_max) then
value_next:=max(value_next,value_max);

EXECUTE ('DELETE FROM '|| increment_table ||' WHERE increment_key= '''||increment_key || '''');
EXECUTE ('INSERT INTO '|| increment_table
	||' (increment_key,increment_value,increment_lastval,table_name,column_name) '
    ||' VALUES('''||increment_key||''','||value_next||','||value_lastval||','''||$1||''','''||increment_column_name||''')');

end if;

RETURN value_lastval;

END;