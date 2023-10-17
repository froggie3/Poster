from dotenv import dotenv_values

env = dotenv_values(".env")
config = env.get("FORECAST_CONFIG") 
place_id = env.get("PLACE_ID") 

