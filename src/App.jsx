import Header from "./components/header";
import Features from "./components/Features";
import Footer from "./components/Footer";

function App() {
  return (
    <div className="min-h-screen flex flex-col">
      <Header />
      <Features />
      <div className="flex-1">
        {/* Nội dung khác sẽ ở đây */}
      </div>
      <Footer />
    </div>
  );
}

export default App;