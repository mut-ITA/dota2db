import json

myDB = pw.MySQLDatabase("databasename", host="hostaddress", port=3306, user="username", passwd="password")
myDB.connect()

with open("heroes.json", 'r') as jf:
    heroes = json.loads(jf.read())['heroes']
    for hero in heroes:
        sqlUpdate = ('insert into heroes(hero_id, hero_name) values ({id}, \'{name}\');').format(str(hero['localized_name']), hero['id'])
        myDB.execute_sql(sqlUpdate)
