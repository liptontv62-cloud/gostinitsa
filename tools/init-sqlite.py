import sqlite3,sys,pathlib
with sqlite3.connect(sys.argv[1]) as db:
    db.executescript((pathlib.Path(__file__).parent.parent/'database/sqlite.sql').read_text())
