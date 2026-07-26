import pandas as pd
import numpy as np
import pymannkendall as mk
import statsmodels.api as sm
import os

from scipy.stats import theilslopes, norm, t
from datetime import datetime
from tabulate import tabulate

file_path = "/Users/mac/Documents/00 Thesis/04 Tesis/data/pos_id_23_trendhidro.csv"
if not os.path.exists(file_path):
    print("File missing")
    exit()

df = pd.read_csv(file_path, encoding='utf-8-sig', delimiter=';', skipinitialspace=True)
df.columns = df.columns.str.strip()

if len(df.columns) == 1:
    df[['Tanggal', 'Data']] = df.iloc[:, 0].str.split(';', expand=True)
if df['Data'].dtype == 'object':
    df['Data'] = df['Data'].astype(str).str.replace(',', '.')

df['Tanggal'] = pd.to_datetime(df['Tanggal'], dayfirst=True, errors='coerce')
df['Data'] = pd.to_numeric(df['Data'], errors='coerce')

df = df.dropna(subset=['Tanggal', 'Data']).reset_index(drop=True)
df = df.sort_values('Tanggal').reset_index(drop=True)

df['Year'] = df['Tanggal'].dt.year
df['Month'] = df['Tanggal'].dt.month
df['YearMonth'] = df['Tanggal'].dt.to_period('M')

all_results = []

def run_trend_calculation(series, name=""):
    data = series.dropna()
    if len(data) < 10: return None
    y = data.values
    x = np.arange(len(y))
    n = len(y)
    
    alpha = 0.05
    mk_result = mk.original_test(y, alpha=alpha)
    mk_z = float(mk_result.z)
    
    sen = theilslopes(y, x, alpha=1-alpha) 
    sen_slope = float(sen.slope)
    
    X = sm.add_constant(x)
    model = sm.OLS(y, X).fit()
    t_value = float(model.tvalues[1])

    all_results.append({
        "Periode": name,
        "n Data": int(n), 
        "MK (Z)": mk_z,
        "SS (Qmed)": sen_slope,
        "RL (t)": t_value
    })
    return

annual = df.groupby('Year')['Data'].sum()
run_trend_calculation(annual, "Tahunan")

print(pd.DataFrame(all_results))
