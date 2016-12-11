drivefrom steam import SteamClient
from dota2 import Dota2Client
import peewee as pw

myDB = pw.MySQLDatabase("databasename", host="hostaddress", port=3306, user="username", passwd="password")
myDB.connect()

client = SteamClient()
dota = Dota2Client(client)

@client.on('logged_on')
def start_dota():
    dota.launch()

@dota.on('ready')
def fetch_profile_card():
    counter = 0
    cursor = myDB.execute_sql('select account_id from users where matchmaking_rating=0;')
    for row in cursor:
        account_id = row[0]
        jobid = dota.request_profile_card(int(account_id))
        profile_card = dota.wait_msg(jobid, timeout=10)

        mmr = '30000'
        if profile_card:
            hasMmr = str(profile_card).find('k_eStat_SoloRank')
            if hasMmr > 0:
                mmrpos = str(profile_card)[hasMmr:].find("stat_score:") + len("stat_score:") + hasMmr
                mmrend = (str(profile_card)[mmrpos:]).find('\n') + mmrpos
                mmr = (str(profile_card)[mmrpos:mmrend])

        sqlUpdate = 'update users set matchmaking_rating=' + str(mmr) + ' where account_id=' + str(account_id) + ';'
        myDB.execute_sql(sqlUpdate)
        counter = counter + 1

        if counter > 100:
            print "+1000 users"
            counter = 0




client.cli_login()
client.run_forever()
