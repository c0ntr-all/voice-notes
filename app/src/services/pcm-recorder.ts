import { downsample, floatToPcm16, mergeFloat32, SAMPLE_RATE } from '@/services/pcm';

interface Recording {
  pcm: ArrayBuffer;
  durationMs: number;
}

/**
 * ScriptProcessor is deprecated, but it records in one file and works in Android WebView.
 * The gain node is muted so the microphone is not played back into the speaker.
 */
export class PcmRecorder {
  private context: AudioContext | null = null;
  private stream: MediaStream | null = null;
  private source: MediaStreamAudioSourceNode | null = null;
  private processor: ScriptProcessorNode | null = null;
  private mute: GainNode | null = null;
  private chunks: Float32Array[] = [];
  private startedAt = 0;
  private inputRate = SAMPLE_RATE;

  get elapsedMs(): number {
    return this.startedAt === 0 ? 0 : performance.now() - this.startedAt;
  }

  async start(): Promise<void> {
    this.stream = await requestMicrophone();
    const context = new AudioContext();
    this.context = context;
    if (context.state === 'suspended') await context.resume();

    this.inputRate = context.sampleRate;
    this.chunks = [];
    this.startedAt = performance.now();
    this.source = context.createMediaStreamSource(this.stream);
    this.processor = context.createScriptProcessor(4096, 1, 1);
    this.processor.onaudioprocess = (event) => {
      this.chunks.push(new Float32Array(event.inputBuffer.getChannelData(0)));
    };
    this.mute = context.createGain();
    this.mute.gain.value = 0;
    this.source.connect(this.processor);
    this.processor.connect(this.mute);
    this.mute.connect(context.destination);
  }

  stop(): Recording {
    const durationMs = this.elapsedMs;
    const inputRate = this.inputRate;
    const chunks = this.chunks;
    this.dispose();

    const pcm = floatToPcm16(downsample(mergeFloat32(chunks), inputRate, SAMPLE_RATE));
    return { pcm, durationMs };
  }

  dispose(): void {
    this.processor?.disconnect();
    this.source?.disconnect();
    this.mute?.disconnect();
    this.stream?.getTracks().forEach((track) => track.stop());
    void this.context?.close();
    this.processor = null;
    this.source = null;
    this.mute = null;
    this.stream = null;
    this.context = null;
    this.chunks = [];
    this.startedAt = 0;
  }
}

async function requestMicrophone(): Promise<MediaStream> {
  if (!navigator.mediaDevices?.getUserMedia) {
    throw new Error(
      'Браузер не даёт доступ к микрофону. Откройте приложение по localhost или HTTPS.',
    );
  }

  try {
    return await navigator.mediaDevices.getUserMedia({
      audio: {
        channelCount: { ideal: 1 },
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true,
      },
    });
  } catch (error) {
    if (error instanceof DOMException && error.name === 'NotAllowedError') {
      throw new Error('Доступ к микрофону запрещён. Разрешите его в браузере и повторите.', {
        cause: error,
      });
    }
    if (error instanceof DOMException && error.name === 'NotFoundError') {
      throw new Error('Микрофон не найден.', { cause: error });
    }

    return navigator.mediaDevices.getUserMedia({ audio: true });
  }
}
