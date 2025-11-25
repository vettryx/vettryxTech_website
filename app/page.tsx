import Image from 'next/image';
// Importamos o novo componente inteligente
import ProjectsArea from '../components/ProjectsArea';
import DynamicForm from '../components/DynamicForm';

export default function Home() {
  // Removemos toda aquela lógica de getProjects daqui. 
  // A página agora é estática e leve.

  return (
    <div className="min-h-screen bg-brand-dark text-white selection:bg-brand-green selection:text-brand-dark">
      
      {/* HEADER */}
      <header className="w-full py-5 px-4 sm:px-8 border-b border-white/10 bg-brand-dark/95 sticky top-0 z-50 backdrop-blur-md">
        <div className="max-w-7xl mx-auto flex justify-between items-center">
          <div className="flex flex-col leading-none">
            <span className="text-2xl font-bold tracking-widest text-white font-rajdhani uppercase">André</span>
            <div className="flex items-center gap-2">
              <div className="h-0.5 w-6 bg-brand-green"></div>
              <span className="text-xl font-bold tracking-widest text-brand-blue font-rajdhani uppercase">Ventura</span>
            </div>
          </div>

          <div className="flex items-center gap-6">
            <div className="hidden md:block text-right text-xs sm:text-sm text-brand-blue font-roboto">
              <p>+55 (31) 9 9190-4415</p>
              <p>contato@asventura.com.br</p>
            </div>
            <a href="#contact" className="bg-brand-green hover:bg-white hover:text-brand-dark text-brand-dark font-bold py-3 px-5 rounded shadow-[0_0_15px_rgba(46,204,64,0.4)] transition-all uppercase text-xs sm:text-sm tracking-wide font-rajdhani">
              Fale Conosco
            </a>
          </div>
        </div>
      </header>

      <main className="w-full">
        
        {/* HERO SECTION */}
        <section className="relative w-full h-[600px] flex items-center justify-start overflow-hidden">
          <div className="absolute inset-0 z-0 bg-brand-dark">
            <Image 
              src="/images/landing.jpg" 
              alt="Background Tech" 
              fill
              priority
              className="object-cover opacity-20 mix-blend-overlay"
              unoptimized
            />
            <div className="absolute inset-0 bg-gradient-to-r from-brand-dark via-brand-dark/80 to-transparent"></div>
          </div>

          <div className="relative z-10 max-w-7xl mx-auto px-4 sm:px-8 w-full">
            <span className="text-brand-orange font-bold font-roboto text-sm tracking-wider uppercase mb-4 block border-l-4 border-brand-orange pl-3">
              André Ventura | Full Stack Developer
            </span>
            <h1 className="text-4xl sm:text-6xl font-semibold text-white leading-[1.1] max-w-3xl font-rajdhani">
              Transformando suas ideias em <br/>
              experiências digitais <span className="text-brand-green">incríveis.</span>
            </h1>
            <p className="mt-6 text-brand-light/80 text-lg max-w-xl font-light font-montserrat">
              Soluções de engenharia robustas, interfaces modernas e automação inteligente para escalar o seu negócio.
            </p>
          </div>
        </section>

        {/* PROJETOS (AGORA DINÂMICO NO CLIENTE) */}
        <section id="projects" className="py-24 bg-brand-dark relative border-t border-white/5">
          <div className="max-w-7xl mx-auto px-4 sm:px-8">
            <div className="flex items-center gap-4 mb-16">
              <h2 className="text-3xl font-bold text-white uppercase whitespace-nowrap font-rajdhani">
                Nossos Projetos
              </h2>
              <div className="h-px w-full bg-gradient-to-r from-brand-green to-transparent opacity-50 mt-1"></div>
            </div>
            
            {/* AQUI ENTRA O COMPONENTE QUE BUSCA OS DADOS */}
            <ProjectsArea />
            
          </div>
        </section>

        {/* CONTATO */}
        <section id="contact" className="py-24 bg-[#012233] border-t border-white/5">
          <div className="max-w-3xl mx-auto px-4">
            <h2 className="text-4xl font-bold text-center mb-10 text-white uppercase font-rajdhani">
              Vamos conversar?
            </h2>
            
            {/* Passando o SLUG que você criou no Admin */}
            <DynamicForm slug="contact-form-main" />
            
          </div>
        </section>

      </main>
    </div>
  );
}