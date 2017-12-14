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


if __name__ == "__main__":
    main()
