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
    for key, val in growth_rate(df).items():
        print(key + "|" + str(val))


def growth_rate(df):
    """Calculate the growth rate for each column of df. The growth rate is
    calculated using the endpoints as if they were on the linear regression
    line."""
    result = {}
    for col in df.columns:
        # We have to mask the inputs to np.polyfit or else we will get NaNs as
        # outputs (if there is even a single NaN in the input)
        logs = np.log(df[col])
        mask = logs.notnull()
        slope, intercept = np.polyfit(df.index.astype(np.int64)[mask],
                                      logs[mask], 1)

        # Get the first and last non-null time values for this column
        first_x = logs[mask].index.astype(np.int64)[0]
        last_x = logs[mask].index.astype(np.int64)[-1]

        first_y = slope * first_x + intercept
        last_y = slope * last_x + intercept

        growth = np.exp(last_y - first_y) - 1.0
        result[col] = growth

    return result

if __name__ == "__main__":
    main()
