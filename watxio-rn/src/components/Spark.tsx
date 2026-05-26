// src/components/Spark.tsx — tiny SVG sparkline. Used in KPI cards + tracking.

import React from 'react';
import Svg, { Path } from 'react-native-svg';

interface Props {
  data: number[];
  width?: number;
  height?: number;
  color: string;
  fill?: string;
}

export function Spark({ data, width = 80, height = 28, color, fill }: Props) {
  if (!data || data.length < 2) return null;
  const min = Math.min(...data);
  const max = Math.max(...data);
  const span = max - min || 1;
  const stepX = width / (data.length - 1);
  const pts = data.map((v, i) => `${i * stepX},${(height - ((v - min) / span) * height).toFixed(2)}`);
  const lineD = 'M ' + pts.join(' L ');
  const areaD = `${lineD} L ${width},${height} L 0,${height} Z`;
  return (
    <Svg width={width} height={height}>
      {fill ? <Path d={areaD} fill={fill} opacity={0.3} /> : null}
      <Path d={lineD} stroke={color} strokeWidth={1.6} fill="none" strokeLinejoin="round" strokeLinecap="round" />
    </Svg>
  );
}
