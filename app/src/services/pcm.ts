export const SAMPLE_RATE = 16_000;
export const MAX_RECORDING_MS = 30_000;
export const MIN_RECORDING_MS = 400;

export function mergeFloat32(chunks: Float32Array[]): Float32Array {
  const length = chunks.reduce((sum, chunk) => sum + chunk.length, 0);
  const merged = new Float32Array(length);
  let offset = 0;

  for (const chunk of chunks) {
    merged.set(chunk, offset);
    offset += chunk.length;
  }

  return merged;
}

export function downsample(
  input: Float32Array,
  inputRate: number,
  targetRate: number,
): Float32Array {
  if (inputRate === targetRate) return input;
  if (inputRate < targetRate) return input;

  const ratio = inputRate / targetRate;
  const length = Math.round(input.length / ratio);
  const result = new Float32Array(length);
  let offset = 0;

  for (let index = 0; index < length; index += 1) {
    const next = Math.min(input.length, Math.round((index + 1) * ratio));
    let sum = 0;
    let count = 0;

    for (let cursor = offset; cursor < next; cursor += 1) {
      sum += input[cursor] ?? 0;
      count += 1;
    }

    result[index] = count > 0 ? sum / count : 0;
    offset = next;
  }

  return result;
}

/** 16-bit signed little-endian PCM at 16 kHz. The WAV upload wraps this buffer. */
export function floatToPcm16(input: Float32Array): ArrayBuffer {
  const buffer = new ArrayBuffer(input.length * 2);
  const view = new DataView(buffer);

  for (let index = 0; index < input.length; index += 1) {
    const sample = Math.max(-1, Math.min(1, input[index] ?? 0));
    view.setInt16(index * 2, sample < 0 ? sample * 0x8000 : sample * 0x7fff, true);
  }

  return buffer;
}

export function pcmToWav(pcm: ArrayBuffer, sampleRate = SAMPLE_RATE): Blob {
  const header = new ArrayBuffer(44);
  const view = new DataView(header);
  const dataSize = pcm.byteLength;

  writeAscii(view, 0, 'RIFF');
  view.setUint32(4, 36 + dataSize, true);
  writeAscii(view, 8, 'WAVE');
  writeAscii(view, 12, 'fmt ');
  view.setUint32(16, 16, true);
  view.setUint16(20, 1, true);
  view.setUint16(22, 1, true);
  view.setUint32(24, sampleRate, true);
  view.setUint32(28, sampleRate * 2, true);
  view.setUint16(32, 2, true);
  view.setUint16(34, 16, true);
  writeAscii(view, 36, 'data');
  view.setUint32(40, dataSize, true);

  return new Blob([header, pcm], { type: 'audio/wav' });
}

function writeAscii(view: DataView, offset: number, value: string): void {
  for (let index = 0; index < value.length; index += 1) {
    view.setUint8(offset + index, value.charCodeAt(index));
  }
}
