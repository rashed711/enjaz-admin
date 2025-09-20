import React, { Component, ErrorInfo, ReactNode } from 'react';

interface Props {
  children: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: ErrorInfo | null;
}

class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false,
    error: null,
    errorInfo: null,
  };

  public static getDerivedStateFromError(error: Error): State {
    return { hasError: true, error: error, errorInfo: null };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error("Uncaught error:", error, errorInfo);
    this.setState({ error: error, errorInfo: errorInfo });
  }

  public render() {
    if (this.state.hasError) {
      return (
        <div className="p-8 bg-red-50 text-red-900 min-h-screen" dir="ltr">
          <h1 className="text-3xl font-bold mb-4">حدث خطأ فادح في التطبيق</h1>
          <p className="mb-6">الرجاء نسخ تفاصيل الخطأ أدناه وإرسالها للدعم الفني.</p>
          <pre className="bg-white p-6 rounded-lg shadow-md text-sm whitespace-pre-wrap font-mono overflow-auto">{this.state.error && this.state.error.toString()}<br />{this.state.errorInfo?.componentStack}</pre>
        </div>
      );
    }
    return this.props.children;
  }
}

export default ErrorBoundary;