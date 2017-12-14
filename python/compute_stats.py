#!/usr/bin/env python3

import argparse
import pandas as pd
import numpy as np

import graph


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("csv", type=str, help="the CSV file to graph")
    args = parser.parse_args()
    df = graph.get_df(args.csv)

    avg = df.mean()
    growth = df.pct_change()
    log_growth = np.log(df).pct_change()

    print(avg.to_csv(sep='|'))
    print("======")
    print(growth.to_csv(sep='|'))
    print("======")
    print(log_growth.to_csv(sep='|'))


def growth_rate(df):
    """Calculate the growth rate for each column of df. The growth rate is
    calculated using the endpoints as if they were on the linear regression
    line."""
    result = {}
    for col in df.columns:
        mask = df[col].notnull()
        slope, intercept = np.polyfit(df.index.astype(np.int64)[mask],
                                      df[col][mask], 1)
        # Get the first non-null time values for this column
        first_x = df[col][mask].index.astype(np.int64)[0]
        last_x = df[col][mask].index.astype(np.int64)[-1]
        first_y = slope * first_x + intercept
        last_y = slope * first_x + intercept
        growth = (last_y - first_y) / first_y
        result[col] = growth
    return result

if __name__ == "__main__":
    main()
